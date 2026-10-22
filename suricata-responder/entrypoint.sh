#!/bin/bash
set -uo pipefail
INTERVAL="${RUN_INTERVAL_SECONDS:-900}"

while true; do
  echo "=== run $(date -Is) ==="
  timeout 600 claude -p "$(cat /app/prompt.md)" \
    --mcp-config /app/mcp.json --strict-mcp-config \
    --allowedTools "mcp__suricata-nsg__get_suricata_alerts,mcp__suricata-nsg__list_blocked_ips,mcp__suricata-nsg__block_ips" \
    --disallowedTools "Bash,Edit,Write,WebFetch,WebSearch,mcp__suricata-nsg__unblock_ips" \
    --max-turns 15 \
    || echo "run failed or timed out (exit $?)"
  sleep "$INTERVAL"
done