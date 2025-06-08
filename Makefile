# Use this way: make exec cmd="php artisan migrate:fresh"
exec:
ifndef cmd
	$(error Please provide a command via cmd, e.g. make exec cmd="php artisan migrate:fresh")
endif
	docker compose -f compose.dev.yaml exec workspace bash -lc "$(cmd)"

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
	make exec cmd="composer install"

migrate-fresh:
	make exec cmd="php artisan migrate:fresh"

seed:
	make exec cmd="php artisan db:seed"

npm-run-build:
	make exec cmd="npm run build"

app-key-gen: #not used, left for occasional use
	make exec cmd="php artisan key:generate"

npm-i:
	make exec cmd="npm install"

npm-run-dev-detached:
	make exec cmd="npm run dev -d"

down:
	docker compose -f compose.dev.yaml --env-file .env.local down --remove-orphans

recreate-test-db:
	make exec cmd="php artisan app:recreate-test-database"

clear-cache:
	make exec cmd="php artisan cache:clear"
	make exec cmd="php artisan config:clear"
	make exec cmd="php artisan route:clear"
	make exec cmd="php artisan view:clear"
	make exec cmd="php artisan event:clear"

check:
	make ini-testing
	make exec cmd="php artisan test --parallel --recreate-databases" || { EXIT_CODE=$$?; make delete-temp-env; make down; exit $$EXIT_CODE; }
	make delete-temp-env
	make down

printenv-workspace:
	docker compose -f compose.dev.yaml exec workspace bash -lc printenv

copy-env-dusk:
	#docker compose -f compose.dev.yaml --env-file .env.dusk.testing exec workspace cp .env.dusk.testing .env
	cp .env.dusk.testing .env

delete-temp-env:
	rm -f .env
