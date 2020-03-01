all: test

phpunit:
	php vendor/bin/phpunit

lint:
	php vendor/bin/php-cs-fixer fix --diff
	php vendor/bin/phpcs --report=code
	php vendor/bin/phpstan analyse

ci:
	php vendor/bin/php-cs-fixer fix --dry-run --diff --ansi
	php vendor/bin/phpcs --report=code
	php vendor/bin/phpstan analyse
	php vendor/bin/phpunit

test: lint phpunit
