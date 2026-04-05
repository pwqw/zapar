.PHONY: help build dev test stop clean logs shell hot-rm

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

test: ## Run frontend tests (vp test)
	@docker stop $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_TEST) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_TEST) \
		-v $(PWD):/var/www/html \
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		$(IMAGE_NAME) \
		sh -c "vp test"

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
