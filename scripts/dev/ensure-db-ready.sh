#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

ENV_FILE="${ENV_FILE:-.env}"
DB_DUMP_FILE="${DB_DUMP_FILE:-deebuk_platformdb.real.11022026.sql}"
MIN_TABLES="${MIN_TABLES:-}"
FORCE_IMPORT="${FORCE_IMPORT:-0}"
STATUS_ONLY="${STATUS_ONLY:-0}"
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
      DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS)
        if [[ -z "${!key:-}" ]]; then
          export "$key=$value"
        fi
        ;;
    esac
  done < "$file"
}

select_db_client() {
  if [[ -n "$DB_CLIENT" ]]; then
    if ! command -v "$DB_CLIENT" >/dev/null 2>&1; then
      echo "ERROR: DB client not found: $DB_CLIENT" >&2
      exit 1
    fi
    return 0
  fi

  if command -v mariadb >/dev/null 2>&1; then
    DB_CLIENT="mariadb"
    return 0
  fi

  if command -v mysql >/dev/null 2>&1; then
    DB_CLIENT="mysql"
    return 0
  fi

  echo "ERROR: Required command not found: mariadb or mysql" >&2
  exit 1
}

infer_min_tables_from_dump() {
  local file="$1"
  [[ -f "$file" ]] || return 1
  local count
  count="$(grep -c '^CREATE TABLE' "$file" 2>/dev/null || true)"
  [[ "$count" =~ ^[0-9]+$ ]] || return 1
  (( count > 0 )) || return 1
  echo "$count"
}

load_env_file "$ENV_FILE"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-deebuk_platform}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

if [[ ! "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]]; then
  echo "ERROR: Invalid DB_NAME '$DB_NAME'" >&2
  exit 1
fi

if [[ -z "$MIN_TABLES" ]]; then
  if inferred_min_tables="$(infer_min_tables_from_dump "$DB_DUMP_FILE")"; then
    MIN_TABLES="$inferred_min_tables"
    echo "MIN_TABLES not set, auto-detected from dump: $MIN_TABLES"
  else
    MIN_TABLES="50"
    echo "MIN_TABLES not set and dump not found/readable, using fallback: $MIN_TABLES"
  fi
fi

if [[ ! "$MIN_TABLES" =~ ^[0-9]+$ ]]; then
  echo "ERROR: MIN_TABLES must be numeric, got '$MIN_TABLES'" >&2
  exit 1
fi

if [[ -n "$DB_PASS" ]]; then
  export MYSQL_PWD="$DB_PASS"
fi

select_db_client

mysql_base=("$DB_CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --default-character-set=utf8mb4)

run_query() {
  "${mysql_base[@]}" -Nse "$1"
}

table_count() {
  run_query "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'" 2>/dev/null || echo "0"
}

safe_table_rows() {
  local table_name="$1"
  run_query "SELECT COUNT(*) FROM \`${DB_NAME}\`.\`${table_name}\`" 2>/dev/null || echo "0"
}

is_ready() {
  local tables teachers systems
  tables="$(table_count)"
  teachers="$(safe_table_rows "teacher")"
  systems="$(safe_table_rows "thesystem")"

  if [[ "$tables" =~ ^[0-9]+$ && "$teachers" =~ ^[0-9]+$ && "$systems" =~ ^[0-9]+$ ]]; then
    if (( tables >= MIN_TABLES && teachers > 0 && systems > 0 )); then
      return 0
    fi
  fi
  return 1
}

print_status() {
  local tables teachers systems
  tables="$(table_count)"
  teachers="$(safe_table_rows "teacher")"
  systems="$(safe_table_rows "thesystem")"

  echo "DB Host: $DB_HOST:$DB_PORT"
  echo "DB Name: $DB_NAME"
  echo "Dump: $DB_DUMP_FILE"
  echo "Min required tables: $MIN_TABLES"
  echo "Tables: $tables"
  echo "teacher rows: $teachers"
  echo "thesystem rows: $systems"

  if is_ready; then
    echo "Status: READY"
  else
    echo "Status: NOT_READY"
  fi
}

import_dump() {
  if [[ ! -f "$DB_DUMP_FILE" ]]; then
    echo "ERROR: Dump file not found: $DB_DUMP_FILE" >&2
    exit 1
  fi

  echo "Resetting database '$DB_NAME'..."
  run_query "DROP DATABASE IF EXISTS \`${DB_NAME}\`;"
  run_query "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

  echo "Importing dump: $DB_DUMP_FILE"
  "${mysql_base[@]}" "$DB_NAME" < "$DB_DUMP_FILE"

  if ! is_ready; then
    echo "ERROR: Import finished but database validation failed." >&2
    print_status
    exit 1
  fi

  echo "Database import complete."
  print_status
}

if [[ "$STATUS_ONLY" == "1" ]]; then
  print_status
  exit 0
fi

if [[ "$FORCE_IMPORT" == "1" ]]; then
  import_dump
  exit 0
fi

if is_ready; then
  echo "Database already ready (skip import)."
  print_status
  exit 0
fi

echo "Database not ready. Running full import..."
import_dump
