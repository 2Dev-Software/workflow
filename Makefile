SHELL := /bin/bash

PHP ?= php
COMPOSER ?= composer
HOST ?= 127.0.0.1
PORT ?= 8000
DB_DUMP_FILE ?= docker/mysql/initdb/001_deebuk_platform.sql
MIN_TABLES ?=

.DEFAULT_GOAL := help

.PHONY: help env deps db-ready db-import db-status smoke test-baseline test-phpunit test-pest test-static test-all lint lint-php lint-composer refactor dev docker-db-dump docker-runtime-assets docker-package docker-up docker-down docker-reset docker-logs

help:
	@echo "Available targets:"
	@echo "  make env        - Create .env from .env.example if missing"
	@echo "  make deps       - Check PHP extensions and install dependencies"
	@echo "  make db-ready   - Ensure DB is fully seeded (auto min tables from dump + core data)"
	@echo "  make db-import  - Force re-import database dump (drop/create DB)"
	@echo "  make db-status  - Print database readiness status"
	@echo "  make smoke      - Run basic runtime checks"
	@echo "  make test-baseline - Run regression baseline checks against the active runtime"
	@echo "  make test-phpunit - Run PHPUnit integration tests"
	@echo "  make test-pest  - Run Pest integration tests"
	@echo "  make test-static - Run PHPStan static analysis on critical workflow code"
	@echo "  make test-all   - Run baseline, PHPUnit, Pest, PHPStan, and lint checks"
	@echo "  make lint       - Validate composer metadata and PHP syntax"
	@echo "  make lint-php   - Validate PHP syntax across project"
	@echo "  make lint-composer - Validate composer.json and composer.lock"
	@echo "  make refactor   - Run consistent non-breaking PHP refactor style pass"
	@echo "  make dev        - Setup everything and start local server"
	@echo "  make docker-db-dump - Export current DB into docker init seed"
	@echo "  make docker-runtime-assets - Package runtime uploads/profile assets for Docker handoff"
	@echo "  make docker-package - Build one handoff package with DB dump and runtime assets"
	@echo "  make docker-up  - Start Docker app + DB"
	@echo "  make docker-down - Stop Docker services"
	@echo "  make docker-reset - Stop Docker services and remove DB volume"
	@echo "  make docker-logs - Tail Docker logs"
	@echo ""
	@echo "Optional overrides:"
	@echo "  HOST=127.0.0.1 PORT=8000 DB_DUMP_FILE=docker/mysql/initdb/001_deebuk_platform.sql"
	@echo "  MIN_TABLES=33 FORCE_IMPORT=1"

env:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "Created .env from .env.example"; \
	else \
		echo ".env already exists (skip)"; \
	fi

deps:
	@PHP="$(PHP)" bash scripts/dev/check-php-exts.sh
	@$(COMPOSER) install --no-interaction --prefer-dist

db-ready:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" bash scripts/dev/ensure-db-ready.sh

db-import:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" FORCE_IMPORT=1 bash scripts/dev/ensure-db-ready.sh

db-status:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" STATUS_ONLY=1 bash scripts/dev/ensure-db-ready.sh

smoke:
	@$(PHP) scripts/smoke-test.php

test-baseline:
	@bash scripts/run-baseline-tests.sh

test-phpunit:
	@bash scripts/run-phpunit-tests.sh

test-pest:
	@bash scripts/run-pest-tests.sh

test-static:
	@bash scripts/run-static-analysis.sh

test-all: test-baseline test-phpunit test-pest test-static lint

lint:
	@bash scripts/run-php-lint.sh

lint-php:
	@bash scripts/run-php-lint.sh --php-only

lint-composer:
	@$(COMPOSER) validate --strict

refactor:
	@./vendor/bin/php-cs-fixer fix --config=php-cs-fixer.dist.php --using-cache=yes --verbose

dev: env deps db-ready smoke
	@echo "Starting PHP server at http://$(HOST):$(PORT)"
	@$(PHP) -S $(HOST):$(PORT)

docker-db-dump:
	@bash scripts/docker/export-current-db.sh

docker-runtime-assets:
	@bash scripts/docker/export-runtime-assets.sh

docker-package:
	@bash scripts/docker/package-handoff.sh

docker-up:
	@docker compose --env-file .env.docker up -d --build

docker-down:
	@docker compose --env-file .env.docker down

docker-reset:
	@docker compose --env-file .env.docker down -v

docker-logs:
	@docker compose --env-file .env.docker logs -f
