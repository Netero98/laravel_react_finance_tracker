run:
ifndef cmd
	$(error Please provide a command via cmd, e.g. make run cmd="php artisan")
endif
	docker compose -f compose.dev.yaml run --rm workspace-cli bash -lc "$(cmd)"

ini:
	make prepare-env
	make down
	make up-detached
	make prepare-running-containers

init:
	make prepare-env
	make down
	make build-pull
	make up-detached-build
	make prepare-running-containers

prepare-running-containers:
	make composer-i
	make create-test-db
	make migrate-fresh
	make seed app-key-gen
	make clear-cache

prepare-env:
	@if [ ! -f .env ]; then \
		echo "Copying .env.example to .env"; \
		cp .env.example .env; \
	else \
		echo ".env already exists. Skipping."; \
	fi
build-pull:
	docker compose -f compose.dev.yaml build --pull
up-detached-build:
	docker compose -f compose.dev.yaml up -d --build
up-detached:
	docker compose -f compose.dev.yaml up -d
up:
	docker compose -f compose.dev.yaml up
composer-i:
	make run cmd="composer install"
migrate-fresh:
	make run cmd="php artisan migrate:fresh"
seed:
	make run cmd="php artisan db:seed"
app-key-gen:
	make run cmd="php artisan key:generate"
npm-i:
	make run cmd="npm install"
npm-run-dev:
	make exec cmd="npm run dev"
down:
	docker compose -f compose.dev.yaml down --remove-orphans
create-test-db:
	make run cmd="php artisan app:recreate-test-database"
clear-cache:
	make run cmd="php artisan cache:clear"
	make run cmd="php artisan config:clear"
	make run cmd="php artisan route:clear"
	make run cmd="php artisan view:clear"
	make run cmd="php artisan event:clear"
test:
	make run cmd="php artisan test --parallel"
