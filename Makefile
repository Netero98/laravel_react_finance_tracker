# Use this way: make exec cmd="php artisan migrate:fresh"
exec:
ifndef cmd
	$(error Please provide a command via cmd, e.g. make exec cmd="php artisan migrate:fresh")
endif
	docker compose -f compose.dev.yaml exec workspace bash -lc "$(cmd)"

init: prepare-env down up-detached composer-i migrate-fresh seed app-key-gen npm-i npm-run-dev-detached

prepare-env:
	@if [ ! -f .env ]; then \
		echo "Copying .env.example to .env"; \
		cp .env.example .env; \
	else \
		echo ".env already exists. Skipping."; \
	fi
up-detached:
	docker compose -f compose.dev.yaml up -d
up:
	docker compose -f compose.dev.yaml up
composer-i:
	docker compose -f compose.dev.yaml exec workspace bash -c "composer install"
migrate-fresh:
	docker compose -f compose.dev.yaml exec workspace bash -c "php artisan migrate:fresh"
seed:
	docker compose -f compose.dev.yaml exec workspace bash -c "php artisan db:seed"
app-key-gen:
	docker compose -f compose.dev.yaml exec workspace bash -c "php artisan key:generate"
npm-i:
	docker compose -f compose.dev.yaml exec workspace bash -lc "npm install"
npm-run-dev-detached:
	docker compose -f compose.dev.yaml exec workspace bash -lc "npm run dev -d"
down:
	docker compose -f compose.dev.yaml down
test:
	make exec cmd="php artisan test --parallel"
