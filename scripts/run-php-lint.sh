#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

run_local() {
  local mode="${1:-}"

  if [[ "$mode" != "--php-only" ]]; then
    composer validate --strict >/dev/null
  fi

  while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
  done < <(find . \
    -path './vendor' -prune -o \
    -path './storage' -prune -o \
    -path './tmp' -prune -o \
    -type f -name '*.php' -print0)

  if [[ "$mode" != "--php-only" ]]; then
    echo "Composer metadata and PHP syntax checks passed."
  else
    echo "PHP syntax check passed."
  fi
}

if [[ "${1:-}" == "--local" ]] || [[ "${1:-}" == "--php-only" ]]; then
  run_local "${1:-}"
  exit 0
fi

if [[ -f "/.dockerenv" ]]; then
  run_local
  exit 0
fi

if command -v docker >/dev/null 2>&1; then
  if docker compose ps app >/dev/null 2>&1; then
    exec docker compose exec -T app bash scripts/run-php-lint.sh --local
  fi
fi

if command -v php >/dev/null 2>&1 && command -v composer >/dev/null 2>&1; then
  run_local
  exit 0
fi

echo "No compatible PHP lint runtime found. Start Docker with 'make docker-up' or install local PHP and Composer." >&2
exit 1
