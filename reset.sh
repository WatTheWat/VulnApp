#!/usr/bin/env bash
# Wipe the database volume and rebuild the shop from db/init.sql.
# Run from the repo root on the VM between rehearsals.
set -euo pipefail
cd "$(dirname "$0")"

echo "[reset] Tearing down containers and the database volume..."
docker compose down -v --remove-orphans

echo "[reset] Clearing web logs..."
rm -f logs/nginx/*.log

echo "[reset] Starting a fresh shop..."
docker compose up -d --build

echo -n "[reset] Waiting for the espresso machine"
for _ in $(seq 1 60); do
  if curl -fs http://localhost/ >/dev/null 2>&1; then
    echo; echo "[reset] Shop is open at http://localhost/"
    exit 0
  fi
  echo -n "."; sleep 2
done
echo; echo "[reset] Shop did not come up. Check: docker compose logs" >&2
exit 1
