# Use this way: make exec cmd="php artisan migrate:fresh"
exec:
ifndef cmd
	$(error Please provide a command via cmd, e.g. make exec cmd="php artisan migrate:fresh")
endif
	docker compose -f compose.dev.yaml exec workspace bash -lc "$(cmd)"

#light init without images rebuild for faster refreshment
ini: prepare-env down up-detached composer-i migrate-fresh seed app-key-gen npm-i npm-run-dev-detached

init: prepare-env down up-detached-build composer-i migrate-fresh seed app-key-gen npm-i npm-run-dev-detached

prepare-env:
	@if [ ! -f .env ]; then \
		echo "Copying .env.example to .env"; \
		cp .env.example .env; \
	else \
		echo ".env already exists. Skipping."; \
	fi
up-detached-build:
	docker compose -f compose.dev.yaml up -d --build
up-detached:
	docker compose -f compose.dev.yaml up -d
up:
	docker compose -f compose.dev.yaml up
composer-i:
	make exec cmd="composer install"
migrate-fresh:
	make exec cmd="php artisan migrate:fresh"
seed:
	make exec cmd="php artisan db:seed"
app-key-gen:
	make exec cmd="php artisan key:generate"
npm-i:
	make exec cmd="npm install"
npm-run-dev-detached:
	make exec cmd="npm run dev -d"
down:
	docker compose -f compose.dev.yaml down
test:
	make exec cmd="APP_ENV=testing php artisan test --parallel"
