.PHONY: help build dev test stop clean logs shell

# Variables
IMAGE_NAME := koel/web
CONTAINER_NAME_DEV := koel-web-dev
CONTAINER_NAME_TEST := koel-web-test

help: ## Mostrar ayuda
	@echo "Comandos disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construir imagen
	docker build -t $(IMAGE_NAME) .

dev: ## Desarrollo con live reload en Docker (puerto 5173). Abre http://localhost:8000
	@docker stop $(CONTAINER_NAME_DEV) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_DEV) \
		-p 8000:8000 \
		-p 5173:5173 \
		-v $(PWD):/var/www/html \
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		-v /var/www/html/storage \
		--env-file .env \
		$(IMAGE_NAME)

test: ## Ejecutar tests
	@docker stop $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_TEST) 2>/dev/null || true
	docker run --rm \
		--name $(CONTAINER_NAME_TEST) \
		-v $(PWD):/var/www/html \
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		$(IMAGE_NAME) \
		sh -c "vp test"

stop: ## Detener contenedores
	@docker stop $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true

clean: ## Limpiar todo (imagen y contenedores)
	@docker stop $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rm $(CONTAINER_NAME_DEV) $(CONTAINER_NAME_TEST) 2>/dev/null || true
	@docker rmi $(IMAGE_NAME) 2>/dev/null || true

logs: ## Ver logs del contenedor dev
	docker logs -f $(CONTAINER_NAME_DEV)

migrate: ## Aplicar migraciones en el contenedor dev
	docker exec $(CONTAINER_NAME_DEV) php artisan migrate --force --no-interaction

shell: ## Shell interactivo en contenedor
	docker run -it --rm \
		-v $(PWD):/var/www/html \
		-v /var/www/html/node_modules \
		-v /var/www/html/.pnpm-store \
		--env-file .env \
		$(IMAGE_NAME) \
		/bin/bash
