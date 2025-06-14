# Finance tracker via Laravel\React\Inertia.js stack

### How are the production and local environments set up?
Based on this: README.md: https://github.com/dockersamples/laravel-docker-examples

### How to set up the project locally?
```
make init
```

### How is project deployed?
Coolify. Steps to deploy from scratch:
- Prepare Coolify instance, domain name should be linked to IP
- Create resource via git -> docker compose
- Add github repo, configure access. Configure deployed branch as master instead of default main 
- Map domain name to nginx container (web) like https://finsly.ru
- Add environments to resource, example - .env.example.
- Configure docker compose build and up commands like this:
- build:
```
COMPOSE_BAKE=true docker compose -f ./compose.prod.yaml build --pull
```

- up:
```
docker compose -f ./compose.prod.yaml up -d --build
```

-Click deploy. Redeploy if need, front is rebuilt and all works fine BUT with a little downtime
