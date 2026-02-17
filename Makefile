SHELL := /bin/bash

PHP ?= php
COMPOSER ?= composer
HOST ?= 127.0.0.1
PORT ?= 8000
DB_DUMP_FILE ?= deebuk_platformdb.real.11022026.sql
MIN_TABLES ?= 50

.DEFAULT_GOAL := help

.PHONY: help env deps db-ready db-import db-status smoke dev

help:
	@echo "Available targets:"
	@echo "  make env        - Create .env from .env.example if missing"
	@echo "  make deps       - Install PHP dependencies via composer"
	@echo "  make db-ready   - Ensure DB is fully seeded (>= $${MIN_TABLES:-50} tables + core data)"
	@echo "  make db-import  - Force re-import database dump (drop/create DB)"
	@echo "  make db-status  - Print database readiness status"
	@echo "  make smoke      - Run basic runtime checks"
	@echo "  make dev        - Setup everything and start local server"
	@echo ""
	@echo "Optional overrides:"
	@echo "  HOST=127.0.0.1 PORT=8000 DB_DUMP_FILE=deebuk_platformdb.real.11022026.sql"
	@echo "  MIN_TABLES=50 FORCE_IMPORT=1"

env:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "Created .env from .env.example"; \
	else \
		echo ".env already exists (skip)"; \
	fi

deps:
	@$(COMPOSER) install --no-interaction --prefer-dist

db-ready:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" bash scripts/dev/ensure-db-ready.sh

db-import:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" FORCE_IMPORT=1 bash scripts/dev/ensure-db-ready.sh

db-status:
	@DB_DUMP_FILE="$(DB_DUMP_FILE)" MIN_TABLES="$(MIN_TABLES)" STATUS_ONLY=1 bash scripts/dev/ensure-db-ready.sh

smoke:
	@$(PHP) scripts/smoke-test.php

dev: env deps db-ready smoke
	@echo "Starting PHP server at http://$(HOST):$(PORT)"
	@$(PHP) -S $(HOST):$(PORT)
