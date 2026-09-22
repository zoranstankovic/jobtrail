.DEFAULT_GOAL := help

COMPOSE := docker compose
# As www-data, so files that make targets create (composer.lock, formatted
# sources, logs) belong to the checkout's owner on Linux; see entrypoint.sh.
EXEC    := $(COMPOSE) exec --user www-data app

.PHONY: help up down fresh test lint shell logs composer npm artisan

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

up: ## Start the whole stack in the background
	$(COMPOSE) up -d

down: ## Stop the stack; data is kept
	$(COMPOSE) down

fresh: ## DESTRUCTIVE: drop every table and reseed the demo data
	$(EXEC) php artisan migrate:fresh --seed

test: ## Run the full check suite: Pint, Larastan and Pest
	$(EXEC) composer test

lint: ## Run every static check without changing files
	$(EXEC) composer lint:check
	$(EXEC) composer types:check
	$(EXEC) npm run types:check
	$(EXEC) npm run check

shell: ## Open a bash shell inside the app container
	$(EXEC) bash

logs: ## Follow the logs of every service
	$(COMPOSE) logs -f

composer: ## Run composer in the app container, e.g. make composer cmd="require foo/bar"
	$(EXEC) composer $(cmd)

npm: ## Run npm in the app container, e.g. make npm cmd="run build"
	$(EXEC) npm $(cmd)

artisan: ## Run artisan in the app container, e.g. make artisan cmd="route:list"
	$(EXEC) php artisan $(cmd)
