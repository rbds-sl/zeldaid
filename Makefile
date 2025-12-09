.PHONY: start stop bash destroy initialize

initialize:
	docker run --rm -v $(PWD):/app -w /app composer:latest composer install --ignore-platform-reqs
	$(MAKE) start

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