# VulnForge — local-only, intentionally vulnerable web app.
VULNFORGE_PORT ?= 8080

.PHONY: up down reset-db logs ps test loglive rotate-logs

up: ## Build and start everything (app on 127.0.0.1:$(VULNFORGE_PORT))
	VULNFORGE_PORT=$(VULNFORGE_PORT) docker compose up --build -d
	@echo "VulnForge is at http://localhost:$(VULNFORGE_PORT)"

down: ## Stop and remove containers
	docker compose down

reset-db: ## Wipe the database volume and re-seed on next start
	docker compose down -v
	VULNFORGE_PORT=$(VULNFORGE_PORT) docker compose up --build -d

logs: ## Tail application logs
	docker compose logs -f app

test: ## Run the plain-PHP logging test suites
	php tests/LoggerTest.php
	php tests/AppEventsTest.php

loglive: ## Follow the structured ECS access log (host side)
	tail -f logs/access.jsonl

rotate-logs: ## Archive current logs and start fresh (between exercises)
	./scripts/rotate-lab-logs.sh

ps: ## Show service status
	docker compose ps
