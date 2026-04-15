.PHONY: help build dev install-deps test-front test-backend test test-all stop clean logs shell hot-rm migrate data

# Variables
IMAGE_NAME := koel/web
CONTAINER_NAME_DEV := koel-web-dev
CONTAINER_NAME_TEST := koel-web-test
# Volúmenes nombrados: persisten entre `docker run --rm` y comparten node_modules / store entre dev, test y shell.
VOL_NODE_MODULES := koel-web_node_modules
VOL_PNPM_STORE := koel-web_pnpm_store

help: ## Show available targets
	@echo "Available targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker image
	docker build -t $(IMAGE_NAME) .

hot-rm: ## Remove public/hot (Vite dev). Uses manifest in public/build; without build: pnpm run build
	@rm -f public/hot && echo "Removed public/hot."

dev: ## Docker dev: Laravel + vp build --watch (production-like, no HMR). http://localhost:8000
	@docker stop $(CONTAINER_NAME_DEV) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_DEV) \
		-p 8000:8000 \
		-v $(PWD):/var/www/html \
		-v $(VOL_NODE_MODULES):/var/www/html/node_modules \
		-v $(VOL_PNPM_STORE):/var/www/html/.pnpm-store \
		-v /var/www/html/storage \
		--env-file .env \
		$(IMAGE_NAME)

# Instala dependencias con red (composer + pnpm). No lo ejecuta `make test`; úsalo tras clonar o al cambiar lockfiles.
install-deps: ## Instala vendor + node_modules en los volúmenes Docker (requiere internet)
	docker run --rm \
		-v $(PWD):/var/www/html \
		-v $(VOL_NODE_MODULES):/var/www/html/node_modules \
		-v $(VOL_PNPM_STORE):/var/www/html/.pnpm-store \
		--entrypoint sh \
		$(IMAGE_NAME) \
		-c 'set -e; cd /var/www/html && composer install --no-interaction --prefer-dist && pnpm install --frozen-lockfile'

# Tests (orden: parciales → suite completa → alias):
# --entrypoint sh evita docker-entrypoint.sh (ese script siempre arranca el servidor).
# Mismos volúmenes que dev: deps ya instaladas sirven offline. No ejecuta pnpm install aquí.
test-front: ## Solo frontend (pnpm run test)
	docker run --rm --name $(CONTAINER_NAME_TEST) --entrypoint sh \
		-v $(PWD):/var/www/html \
		-v $(VOL_NODE_MODULES):/var/www/html/node_modules \
		-v $(VOL_PNPM_STORE):/var/www/html/.pnpm-store \
		$(IMAGE_NAME) -c 'set -e; cd /var/www/html; pnpm run test'

test-backend: ## Solo backend PHP (php artisan test --compact)
	docker run --rm --name $(CONTAINER_NAME_TEST) --entrypoint sh \
		-v $(PWD):/var/www/html \
		$(IMAGE_NAME) -c 'set -e; cd /var/www/html && php artisan test --compact'

test: ## Frontend + backend (pnpm run test && php artisan test --compact)
	docker run --rm --name $(CONTAINER_NAME_TEST) --entrypoint sh \
		-v $(PWD):/var/www/html \
		-v $(VOL_NODE_MODULES):/var/www/html/node_modules \
		-v $(VOL_PNPM_STORE):/var/www/html/.pnpm-store \
		$(IMAGE_NAME) -c 'set -e; cd /var/www/html; pnpm run test && php artisan test --compact'

test-all: test ## Alias de make test (compatibilidad)

stop: ## Stop containers
	@docker stop $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true

clean: ## Remove image and containers
	@docker stop $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rmi $(IMAGE_NAME) 2>/dev/null || true

logs: ## Follow dev container logs
	docker logs -f $(CONTAINER_NAME_DEV)

migrate: ## Run migrations in dev container
	docker exec $(CONTAINER_NAME_DEV) php artisan migrate --force --no-interaction

data: ## Borra y recrea la DB (migrate:fresh --seed) en el contenedor dev
	docker exec $(CONTAINER_NAME_DEV) php artisan migrate:fresh --seed --force --no-interaction

shell: ## Interactive shell in container
	docker run -it --rm \
		-v $(PWD):/var/www/html \
		-v $(VOL_NODE_MODULES):/var/www/html/node_modules \
		-v $(VOL_PNPM_STORE):/var/www/html/.pnpm-store \
		--env-file .env \
		$(IMAGE_NAME) \
		/bin/bash
