build: docker-build
up: docker-up
down: docker-down
restart: docker-down docker-up
init: docker-down-all docker-build docker-up composer-install

docker-build:
	docker compose build --no-cache

docker-up:
	docker-compose up -d
	
docker-down:
	docker-compose down --remove-orphans

docker-down-all:
	docker-compose down -v --remove-orphans

composer-install:
	docker-compose run --rm csv-processor-php-cli composer install

wait-db:
	until docker-compose exec -T csv-processor-postgres pg_isready --timeout=0 --dbname=app ; do sleep 1 ; done

migrations:
	docker-compose run --rm csv-processor-php-cli php bin/console doctrine:migrations:migrate --no-interaction

sh:
	docker-compose exec $(container) sh

logs:
	docker-compose logs --tail=0 --follow

bash:
	docker container exec -it $(container) bash
