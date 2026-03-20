#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

ENV_FILE="${ENV_FILE:-.env}"
OUTPUT_FILE="${OUTPUT_FILE:-docker/mysql/initdb/001_deebuk_platform.sql}"
DB_CLIENT="${DB_CLIENT:-}"

load_env_file() {
  local file="$1"
  [[ -f "$file" ]] || return 0

  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line%$'\r'}"
    [[ -z "$line" || "${line:0:1}" == "#" ]] && continue
    [[ "$line" != *=* ]] && continue

    local key="${line%%=*}"
    local value="${line#*=}"
    case "$key" in
      DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS|DB_CHARSET)
        if [[ -z "${!key:-}" ]]; then
          export "$key=$value"
        fi
        ;;
    esac
  done < "$file"
}

select_db_client() {
  if [[ -n "$DB_CLIENT" ]]; then
    command -v "$DB_CLIENT" >/dev/null 2>&1 || {
      echo "ERROR: DB dump client not found: $DB_CLIENT" >&2
      exit 1
    }
    return 0
  fi

  if command -v mariadb-dump >/dev/null 2>&1; then
    DB_CLIENT="mariadb-dump"
    return 0
  fi

  if command -v mysqldump >/dev/null 2>&1; then
    DB_CLIENT="mysqldump"
    return 0
  fi

  echo "ERROR: Required command not found: mariadb-dump or mysqldump" >&2
  exit 1
}

load_env_file "$ENV_FILE"
select_db_client

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-deebuk_platform}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_CHARSET="${DB_CHARSET:-utf8mb4}"

mkdir -p "$(dirname "$OUTPUT_FILE")"

find "$(dirname "$OUTPUT_FILE")" -maxdepth 1 \
  \( -name '*.sql' -o -name '*.sql.gz' \) \
  ! -name "$(basename "$OUTPUT_FILE")" \
  -delete

echo "Exporting database '$DB_NAME' to '$OUTPUT_FILE' ..."
MYSQL_PWD="$DB_PASS" "$DB_CLIENT" \
  --protocol=TCP \
  -h "$DB_HOST" \
  -P "$DB_PORT" \
  -u "$DB_USER" \
  --default-character-set="$DB_CHARSET" \
  --single-transaction \
  --routines \
  --triggers \
  --hex-blob \
  --skip-comments \
  --skip-dump-date \
  "$DB_NAME" > "$OUTPUT_FILE"

echo "Done."
