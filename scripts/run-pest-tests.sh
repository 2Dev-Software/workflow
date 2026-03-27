#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f "/.dockerenv" ]]; then
  exec vendor/bin/pest --testsuite Pest
fi

if command -v docker >/dev/null 2>&1; then
  if docker compose ps app >/dev/null 2>&1; then
    exec docker compose exec -T app vendor/bin/pest --testsuite Pest
  fi
fi

if [[ -f ".env" ]] && command -v php >/dev/null 2>&1; then
  exec vendor/bin/pest --testsuite Pest
fi

echo "No active app runtime found. Start Docker with 'make docker-up' or provide a local .env before running Pest tests." >&2
exit 1
