.PHONY: start stop bash destroy initialize setup-env clear-cache

initialize:
	docker run --rm -v $(PWD):/app -w /app composer:latest composer install --ignore-platform-reqs
	$(MAKE) start
	$(MAKE) setup-env
	$(MAKE) clear-cache

setup-env:
	cp -n env.sample .env || true

clear-cache:
	./vendor/bin/sail php artisan config:clear
	./vendor/bin/sail php artisan cache:clear

start:
	./vendor/bin/sail up -d

stop:
	./vendor/bin/sail stop

bash:
	./vendor/bin/sail shell

destroy:
	./vendor/bin/sail down -v


phpstan:
	./vendor/bin/sail php ./vendor/bin/phpstan analyse --memory-limit=1G