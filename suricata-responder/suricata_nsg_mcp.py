"""
suricata_nsg_mcp.py — MCP server: Suricata alerts (Log Analytics) -> Azure NSG IP blocking.

pip install "mcp[cli]" azure-identity azure-mgmt-network azure-monitor-query

Env vars:
  AZURE_SUBSCRIPTION_ID   subscription containing the NSG
  NSG_RESOURCE_GROUP      resource group of the NSG
  NSG_NAME                NSG attached to the VM's NIC (or subnet)
  LA_WORKSPACE_ID         Log Analytics workspace ID (GUID) receiving eve.json
  SURICATA_TABLE          custom table name (default: Suricata_CL)
  BLOCK_RULE_NAME         default: suricata-blocklist
  BLOCK_RULE_PRIORITY     default: 100 (must be lower than any Allow rule)
  ALLOWLIST_CIDRS         comma-separated CIDRs that must never be blocked (your home IP, VPN, etc.)
  MAX_BLOCKS_PER_RUN      cap on IPs added per server process / claude -p run (default: 10)
  BLOCK_LOG               optional path for a JSON-lines audit log of applied blocks
Auth: DefaultAzureCredential (az login, env service principal, or managed identity).
"""
import ipaddress
import json
import os
from datetime import datetime, timedelta, timezone

from azure.core.exceptions import ResourceNotFoundError
from azure.identity import DefaultAzureCredential
from azure.mgmt.network import NetworkManagementClient
from azure.mgmt.network.models import SecurityRule
from azure.monitor.query import LogsQueryClient, LogsQueryStatus
from mcp.server.fastmcp import FastMCP

SUB = os.environ["AZURE_SUBSCRIPTION_ID"]
RG = os.environ["NSG_RESOURCE_GROUP"]
NSG = os.environ["NSG_NAME"]
WORKSPACE = os.environ["LA_WORKSPACE_ID"]
TABLE = os.environ.get("SURICATA_TABLE", "Suricata_CL")
RULE_NAME = os.environ.get("BLOCK_RULE_NAME", "suricata-blocklist")
PRIORITY = int(os.environ.get("BLOCK_RULE_PRIORITY", "100"))
MAX_PER_CALL = 25
MAX_BLOCKS_PER_RUN = int(os.environ.get("MAX_BLOCKS_PER_RUN", "10"))
BLOCK_LOG = os.environ.get("BLOCK_LOG")
_blocked_this_run = 0

# Never block these: Azure platform IPs (DHCP/DNS/health probes, IMDS) plus user allowlist
PROTECTED = [ipaddress.ip_network(c) for c in ("168.63.129.16/32", "169.254.169.254/32")]
PROTECTED += [
    ipaddress.ip_network(c.strip(), strict=False)
    for c in os.environ.get("ALLOWLIST_CIDRS", "").split(",") if c.strip()
]

cred = DefaultAzureCredential()
net = NetworkManagementClient(cred, SUB)
logs = LogsQueryClient(cred)
mcp = FastMCP("suricata-nsg")


def _validate(ip: str) -> str | None:
    """Return an error string if ip must not be blocked, else None."""
    try:
        addr = ipaddress.ip_address(ip)
    except ValueError:
        return "not a valid IP"
    if addr.is_private or addr.is_loopback or addr.is_reserved or addr.is_multicast or addr.is_link_local:
        return "private/reserved address"
    if any(addr in n for n in PROTECTED):
        return "protected/allowlisted"
    return None


def _get_rule():
    try:
        return net.security_rules.get(RG, NSG, RULE_NAME)
    except ResourceNotFoundError:
        return None


def _current_blocked() -> list[str]:
    rule = _get_rule()
    if not rule:
        return []
    return list(rule.source_address_prefixes or ([rule.source_address_prefix] if rule.source_address_prefix else []))


def _write_rule(prefixes: list[str], note: str):
    if not prefixes:
        net.security_rules.begin_delete(RG, NSG, RULE_NAME).result()
        return
    rule = SecurityRule(
        protocol="*",
        source_address_prefixes=sorted(set(prefixes)),
        source_port_range="*",
        destination_address_prefix="*",
        destination_port_range="*",
        access="Deny",
        direction="Inbound",
        priority=PRIORITY,
        description=note[:140],
    )
    net.security_rules.begin_create_or_update(RG, NSG, RULE_NAME, rule).result()


@mcp.tool()
def get_suricata_alerts(hours: int = 24, max_severity: int = 2, limit: int = 50) -> list[dict]:
    """Summarize Suricata alerts by source IP from Log Analytics.
    Suricata severity: 1 = highest. max_severity=2 returns severity 1 and 2."""
    kql = f"""
{TABLE}
| where TimeGenerated > ago({int(hours)}h)
| extend e = parse_json(RawData)
| where tostring(e.event_type) == "alert"
| extend src_ip = tostring(e.src_ip), dest_port = toint(e.dest_port),
         sig = tostring(e.alert.signature), sev = toint(e.alert.severity)
| where sev <= {int(max_severity)}
| summarize hits = count(), top_severity = min(sev), signatures = make_set(sig, 10),
            dest_ports = make_set(dest_port, 10), first_seen = min(TimeGenerated),
            last_seen = max(TimeGenerated) by src_ip
| order by top_severity asc, hits desc
| take {int(limit)}
"""
    resp = logs.query_workspace(WORKSPACE, kql, timespan=timedelta(hours=hours))
    if resp.status != LogsQueryStatus.SUCCESS:
        raise RuntimeError(f"query failed or partial: {getattr(resp, 'partial_error', resp)}")
    table = resp.tables[0]
    blocked = set(_current_blocked())
    rows = []
    for r in table.rows:
        row = {c: (str(v) if hasattr(v, "isoformat") else v) for c, v in zip(table.columns, r)}
        row["already_blocked"] = row["src_ip"] in blocked or f"{row['src_ip']}/32" in blocked
        row["blockable"] = _validate(row["src_ip"]) or "yes"
        rows.append(row)
    return rows


@mcp.tool()
def list_blocked_ips() -> dict:
    """List IPs currently in the NSG deny rule."""
    rule = _get_rule()
    return {"rule": RULE_NAME, "nsg": NSG, "ips": _current_blocked(),
            "description": rule.description if rule else None}


@mcp.tool()
def block_ips(ips: list[str], reason: str, dry_run: bool = True) -> dict:
    """Add IPs to the NSG inbound deny rule. Defaults to dry_run=True:
    call once to preview, then again with dry_run=False to apply."""
    if len(ips) > MAX_PER_CALL:
        return {"error": f"max {MAX_PER_CALL} IPs per call"}
    accepted, rejected = [], {}
    for ip in ips:
        err = _validate(ip)
        (rejected.__setitem__(ip, err) if err else accepted.append(ip))
    current = _current_blocked()
    new = [ip for ip in accepted if ip not in current]
    result = {"would_add": new, "rejected": rejected, "already_blocked": [i for i in accepted if i in current],
              "rule": RULE_NAME, "priority": PRIORITY, "dry_run": dry_run}
    if dry_run or not new:
        return result
    global _blocked_this_run
    if _blocked_this_run + len(new) > MAX_BLOCKS_PER_RUN:
        return {**result, "error": f"per-run cap of {MAX_BLOCKS_PER_RUN} reached; nothing applied"}
    _write_rule(current + new, f"suricata: {reason}")
    _blocked_this_run += len(new)
    if BLOCK_LOG:
        with open(BLOCK_LOG, "a") as f:
            f.write(json.dumps({"ts": datetime.now(timezone.utc).isoformat(),
                                "added": new, "reason": reason}) + "\n")
    result["applied"] = True
    return result


@mcp.tool()
def unblock_ips(ips: list[str], dry_run: bool = True) -> dict:
    """Remove IPs from the NSG deny rule (deletes the rule if it becomes empty)."""
    current = _current_blocked()
    remaining = [p for p in current if p not in ips and p.removesuffix("/32") not in ips]
    removed = sorted(set(current) - set(remaining))
    if dry_run or not removed:
        return {"would_remove": removed, "dry_run": dry_run}
    _write_rule(remaining, "suricata: updated")
    return {"removed": removed, "applied": True}


if __name__ == "__main__":
    mcp.run()  # stdio transport