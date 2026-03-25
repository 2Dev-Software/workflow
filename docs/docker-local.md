# Docker Local Workflow

Last updated: 25 March 2026

This stack exists so the project can be opened on the same PHP and MariaDB environment across machines.

## What the Docker stack provides
- PHP + Apache application container
- MariaDB container
- database initialization from the current baseline SQL dump
- migration execution after the database is ready

## Required files
Core Docker files in the repo:
- `docker-compose.yml`
- `.env.docker.example`
- `docker/php/Dockerfile`
- `docker/php/entrypoint.sh`
- `docker/php/apache-vhost.conf`
- `docker/php/php.ini`
- `docker/mysql/initdb/001_deebuk_platform.sql`

## Start the stack
1. Copy environment file

```bash
cp .env.docker.example .env.docker
```

2. Build and start

```bash
docker compose --env-file .env.docker up -d --build
```

Default URLs and ports:
- Web: `http://127.0.0.1:8000`
- DB on host: `127.0.0.1:3307`

If ports collide, change `APP_PORT` and `DB_PORT_HOST` in `.env.docker`.

## Refresh the database baseline
To export the current local database into the Docker seed:

```bash
bash scripts/docker/export-current-db.sh
```

The script keeps the initdb folder clean so the main SQL seed remains a single base file.

## Runtime assets handoff
If another machine also needs runtime uploads and profile images, export them separately:

```bash
bash scripts/docker/export-runtime-assets.sh
```

This creates:
- `docker/runtime-assets/workflow-runtime-assets.tar.gz`

To unpack on the target machine:

```bash
tar -xzf docker/runtime-assets/workflow-runtime-assets.tar.gz
```

## One-command handoff package
To build the handoff artifact:

```bash
make docker-package
```

That package includes:
- Docker stack files
- DB seed
- runtime assets archive
- Docker handoff documentation

## Reset the DB volume
MariaDB imports the init scripts only when the volume is empty.

To force a clean re-import:

```bash
docker compose --env-file .env.docker down -v
docker compose --env-file .env.docker up -d --build
```

## Git policy reminder
The Docker stack is source-controlled.
Runtime data is not.

Keep out of Git:
- `storage/uploads/**`
- `assets/img/profile/**`
- generated temp output
