# Workflow (Deebuk Platform)

Pure PHP workflow system for internal documents, memos, outgoing letters, rooms, and vehicles.

## Quick Start (Production-like local setup)

### Prerequisites

- PHP 8.2+
- Composer
- PHP extensions: `gd`, `iconv`
- MySQL/MariaDB client (`mysql` command)
- Local MySQL/MariaDB server running

For Linux, if `make dev` fails on missing PHP extensions:

```bash
sudo apt update
sudo apt install -y php-cli php-common php-mysql php-mbstring php-xml php-curl php-zip php-gd
```

Then run `make dev` again.

### 1) Clone and enter project

```bash
git clone https://github.com/2Dev-Software/workflow.git
cd workflow
```

### 2) One command to setup + run

```bash
make dev
```

What `make dev` does:

1. Create `.env` from `.env.example` if missing
2. Install PHP dependencies (`composer install`)
3. Ensure DB is fully ready from production dump:
   - expected at least 50 tables
   - must contain core data (`teacher`, `thesystem`)
   - if DB is not ready, it will drop/create DB and import dump file
4. Run smoke checks
5. Start local server at `http://127.0.0.1:8000`

## Database Commands

```bash
make db-status   # show DB readiness (tables + core rows)
make db-ready    # import only if DB is not ready
make db-import   # force re-import (drop/create DB)
```

Optional overrides:

```bash
make dev HOST=0.0.0.0 PORT=8000
make db-import DB_DUMP_FILE=deebuk_platformdb.real.11022026.sql MIN_TABLES=33
```

`MIN_TABLES` is optional. If not provided, the script auto-detects expected table count from the dump file.

## Dump File

Default production data dump file:

`deebuk_platformdb.real.11022026.sql`

This dump is used by `scripts/dev/ensure-db-ready.sh`.

## Project Structure (short)

```text
app/         Controllers, modules, views
assets/      CSS/JS/images
config/      Environment + DB connection bootstrap
migrations/  SQL migrations
scripts/     CLI scripts and dev automation
public/      Public API endpoints and shared components
```
