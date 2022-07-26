#
# Makefile
#

.PHONY: help
.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# ------------------------------------------------------------------------------------------------------------

install: ## Installs all production dependencies
	@composer validate
	@composer install --no-dev
	cd src/Resources/app/administration && npm install --production

dev: ## Installs all dev dependencies
	@composer validate
	@composer install
	cd src/Resources/app/administration && npm install

clean: ## Cleans all dependencies
	rm -rf vendor
	rm -rf ./src/Resources/app/administration/node_modules/*
	rm -rf ./src/Resources/app/administration/package-lock.json

build: ## Installs the plugin, and builds the artifacts using the Shopware build commands (requires Shopware)
	cd /var/www/html && php bin/console plugin:refresh
	cd /var/www/html && php bin/console plugin:install SasBlogModule --activate | true
	cd /var/www/html && php bin/console plugin:refresh
	cd /var/www/html && php bin/console theme:dump
	cd /var/www/html && ./bin/build-js.sh
	cd /var/www/html && php bin/console theme:refresh
	cd /var/www/html && php bin/console theme:compile
	cd /var/www/html && php bin/console theme:refresh

admin: ## Installs all admin dependencies
	cd vendor/shopware/administration/Resources/app/administration && npm install

# ------------------------------------------------------------------------------------------------------------

phpcheck: ## Starts the PHP syntax checks
	@find . -name '*.php' -not -path "./vendor/*" -not -path "./tests/*" | xargs -n 1 -P4 php -l

csfix: ## Starts the PHP CS Fixer
	@php vendor/bin/php-cs-fixer fix --config=./.php_cs.php --dry-run

phpunit: ## Starts all PHPUnit Tests
	@XDEBUG_MODE=coverage php vendor/bin/phpunit --configuration=phpunit.xml --coverage-html ../../../public/.reports/blogmodule/coverage

infection: ## Starts all Infection/Mutation tests
	@XDEBUG_MODE=coverage php vendor/bin/infection --configuration=./.infection.json

jest: ## Starts all Jest tests
	cd ./src/Resources/app/administration && ./node_modules/.bin/jest --config=jest.config.js

eslint: ## Starts the ESLinter
	cd ./src/Resources/app/administration && ./node_modules/.bin/eslint --config ./.eslintrc.json ./src

# ------------------------------------------------------------------------------------------------------------

pr: ## Prepares everything for a Pull Request
	@php vendor/bin/php-cs-fixer fix --config=./.php_cs.php
	@make phpcheck -B
	@make phpunit -B
	@make infection -B
	@make csfix -B
	@make jest -B
	@make eslint -B
