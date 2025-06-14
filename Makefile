# Use this way: make exec cmd="php artisan migrate:fresh"
#exec:
#ifndef cmd
#	$(error Please provide a command via cmd, e.g. make exec cmd="php artisan migrate:fresh")
#endif
#	docker compose -f compose.dev.yaml exec workspace bash -lc "$(cmd)"

run:
	docker compose -f compose.dev.yaml --env-file .env.local run workspace bash -rm -lc "$(cmd)"

# even lighter command just to restart container and vite server for frontend
i: down up-detached npm-run-dev-detached
#light init without images rebuild for faster refreshment
ini: down up-detached composer-i recreate-test-db migrate-fresh seed clear-cache npm-i npm-run-dev-detached

init: down up-detached-build composer-i recreate-test-db migrate-fresh seed clear-cache npm-i npm-run-dev-detached

# Had to copy env file because dusk demand it, it doesnt care that envs already exist in container
ini-testing: down up-detached-testing composer-i recreate-test-db migrate-fresh clear-cache npm-i npm-run-build copy-env-dusk

up-detached-build:
	COMPOSE_BAKE=true docker compose -f  compose.dev.yaml --env-file .env.local up -d --build

up-detached:
	docker compose -f compose.dev.yaml --env-file .env.local up -d

up-detached-testing:
	docker compose -f compose.dev.yaml --env-file .env.dusk.testing up -d

up:
	docker compose -f compose.dev.yaml --env-file .env.local up

composer-i:
	make run cmd="composer install"

migrate-fresh:
	make run cmd="php artisan migrate:fresh"

seed:
	make run cmd="php artisan db:seed"

npm-run-build:
	make run cmd="npm run build"

app-key-gen: #not used, left for occasional use
	make run cmd="php artisan key:generate"

npm-i:
	make run cmd="npm install"

npm-run-dev-detached:
	make run cmd="npm run dev -d"

down:
	docker compose -f compose.dev.yaml --env-file .env.local down --remove-orphans > /dev/null 2>&1 && echo "Containers stopped and removed."

recreate-test-db:
	make run cmd="php artisan app:recreate-test-database"

clear-cache:
	make run cmd="php artisan cache:clear"
	make run cmd="php artisan config:clear"
	make run cmd="php artisan route:clear"
	make run cmd="php artisan view:clear"
	make run cmd="php artisan event:clear"

check:
	make ini-testing
	rm -f public/hot
	make run cmd="php artisan test --parallel --recreate-databases" || { EXIT_CODE=$$?; make clear-after-e2e; exit $$EXIT_CODE; }
	make clear-after-e2e

clear-after-e2e:
	make delete-temp-env
	make down

printenv-workspace:
	make run cmd="printenv"

copy-env-dusk:
	cp .env.dusk.testing .env

delete-temp-env:
	rm -f .env
