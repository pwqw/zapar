.PHONY: help build dev test test-backend test-all stop clean logs shell hot-rm migrate

# Variables
IMAGE_NAME := koel/web
CONTAINER_NAME_DEV := koel-web-dev
CONTAINER_NAME_TEST := koel-web-test

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
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		-v /var/www/html/storage \
		--env-file .env \
		$(IMAGE_NAME)

# Tests: --entrypoint sh evita docker-entrypoint.sh (ese script siempre arranca el servidor).
# Sin volumen anónimo en node_modules: si no, Docker tapa el bind mount y el dir queda vacío
# (en dev el entrypoint hace pnpm install; aquí no). --rm elimina el contenedor al terminar.
test: ## Run frontend + PHP tests (salida solo de los tests)
	@docker stop $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_TEST) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_TEST) \
		--entrypoint sh \
		-v $(PWD):/var/www/html \
		$(IMAGE_NAME) \
		-c 'set -e; cd /var/www/html && pnpm run test && php artisan test --compact'

test-backend: ## Solo PHP (php artisan test --compact)
	@docker stop $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_TEST) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_TEST) \
		--entrypoint sh \
		-v $(PWD):/var/www/html \
		$(IMAGE_NAME) \
		-c 'set -e; cd /var/www/html && php artisan test --compact'

test-all: test ## Igual que make test (compatibilidad)

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

shell: ## Interactive shell in container
	docker run -it --rm \
		-v $(PWD):/var/www/html \
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		--env-file .env \
		$(IMAGE_NAME) \
		/bin/bash
