IS_RUNNING := $(shell docker inspect dm-laravel 2>/dev/null | grep "Running" | xargs | sed "s/.$$//")
RUNNING := Running: true
$(if $(IS_RUNNING), $(info ### Laravel container ${IS_RUNNING} ###), $(info ### Laravel container Running: false ###))

setup: build up install

build:
	docker-compose build --pull

up:
	docker-compose up -d

down:
	docker-compose down

destroy:
	docker-compose down -v
	docker rmi "dm-api:$(or $(NODE_IMAGE_TAG),latest)" || true

logs:
	docker-compose logs --follow $(filter-out $@,$(MAKECMDGOALS))

install:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel composer install --no-cache --optimize-autoloader --prefer-dist
	docker-compose exec laravel composer validate --strict
	docker-compose exec laravel php artisan optimize:clear
else
	docker-compose run --rm laravel composer install --no-cache --optimize-autoloader --prefer-dist
	docker-compose run --rm laravel composer validate --strict
	docker-compose run --rm laravel php artisan optimize:clear
endif

bash:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel bash
else
	docker-compose run --rm laravel bash
endif

artisan:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel php artisan $(filter-out $@,$(MAKECMDGOALS))
else
	docker-compose run --rm laravel php artisan $(filter-out $@,$(MAKECMDGOALS))
endif

tinker:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel php artisan tinker
else
	docker-compose run --rm laravel php artisan tinker
endif

migrate:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel php artisan migrate $(filter-out $@,$(MAKECMDGOALS))
else
	docker-compose run --rm laravel php artisan migrate $(filter-out $@,$(MAKECMDGOALS))
endif

fresh-migrate:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel bash -c "php artisan env:setup && php artisan migrate:fresh --path=database/testing-migrations -vvv"
else
	docker-compose run --rm laravel bash -c "php artisan env:setup && php artisan migrate:fresh --path=database/testing-migrations -vvv"
endif

test:
ifeq ($(IS_RUNNING), $(RUNNING))
	docker-compose exec laravel bash ./scripts/acceptance-test $(filter-out $@,$(MAKECMDGOALS))
else
	docker-compose run --rm laravel bash ./scripts/acceptance-test $(filter-out $@,$(MAKECMDGOALS))
endif

.PHONY: artisan
.EXPORT_ALL_VARIABLES:
UID := $(shell id -u)
