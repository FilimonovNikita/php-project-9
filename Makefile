PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

startLocal:
	php -S localhost:8080 -t public public/index.php

install: # установить зависимости
	composer install
validate: # проверка файла composer.json
	composer validate

lint: # запуск phpcs
	composer exec --verbose phpcs -- --standard=PSR12 app public
