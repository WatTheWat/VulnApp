Pull Suricata alerts from the last 1 hour with max_severity 1.
Ignore any IP where already_blocked is true or blockable is not "yes".
Block source IPs that have clear exploit, scanning or brute-force signatures AND 5 or more hits.
Call block_ips with dry_run=false and a short reason naming the signature.
Skip anything ambiguous. Finish with one line per IP considered: BLOCKED or SKIPPED, and why.