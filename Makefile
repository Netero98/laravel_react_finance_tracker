init: add-or-pass-env down up-detached composer-i migrate-fresh seed app-key-gen npm-i npm-dev

add-or-pass-env:
	cp -n .env.example .env
up:
	docker compose up
up-detached:
	docker compose up -d
composer-i:
	/bin/bash sail composer install
migrate-fresh:
	/bin/bash sail artisan migrate:fresh
seed:
	/bin/bash sail artisan db:seed
app-key-gen:
	/bin/bash sail artisan key:generate
npm-i:
	/bin/bash sail npm i
npm-dev:
	/bin/bash sail npm run dev -d
down:
	docker compose down
