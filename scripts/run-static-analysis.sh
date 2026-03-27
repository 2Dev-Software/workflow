#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

run_local() {
  exec vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
}

if [[ "${1:-}" == "--local" ]]; then
  run_local
fi

if [[ -f "/.dockerenv" ]]; then
  run_local
fi

if command -v docker >/dev/null 2>&1; then
  if docker compose ps app >/dev/null 2>&1; then
    exec docker compose exec -T app bash scripts/run-static-analysis.sh --local
  fi
fi

if [[ -f ".env" ]] && command -v php >/dev/null 2>&1; then
  run_local
fi

echo "No active app runtime found. Start Docker with 'make docker-up' or provide a local .env before running static analysis." >&2
exit 1
