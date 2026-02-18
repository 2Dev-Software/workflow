SHELL := /bin/bash

PHP ?= php
COMPOSER ?= composer
HOST ?= 127.0.0.1
PORT ?= 8000
DB_DUMP_FILE ?= deebuk_platformdb.real.11022026.sql
MIN_TABLES ?=

.DEFAULT_GOAL := help

.PHONY: help env deps db-ready db-import db-status smoke lint-php refactor dev

help:
	@echo "Available targets:"
	@echo "  make env        - Create .env from .env.example if missing"
	@echo "  make deps       - Check PHP extensions and install dependencies"
	@echo "  make db-ready   - Ensure DB is fully seeded (auto min tables from dump + core data)"
	@echo "  make db-import  - Force re-import database dump (drop/create DB)"
	@echo "  make db-status  - Print database readiness status"
	@echo "  make smoke      - Run basic runtime checks"
	@echo "  make lint-php   - Validate PHP syntax across project"
	@echo "  make refactor   - Run consistent non-breaking PHP refactor style pass"
	@echo "  make dev        - Setup everything and start local server"
	@echo ""
	@echo "Optional overrides:"
	@echo "  HOST=127.0.0.1 PORT=8000 DB_DUMP_FILE=deebuk_platformdb.real.11022026.sql"
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

lint-php:
	@find . \
		-path './vendor' -prune -o \
		-path './storage' -prune -o \
		-path './tmp' -prune -o \
		-type f -name '*.php' -print | \
		xargs -I{} $(PHP) -l "{}" >/dev/null
	@echo "PHP syntax check passed."

refactor:
	@./vendor/bin/php-cs-fixer fix --config=php-cs-fixer.dist.php --using-cache=yes --verbose

dev: env deps db-ready smoke
	@echo "Starting PHP server at http://$(HOST):$(PORT)"
	@$(PHP) -S $(HOST):$(PORT)
