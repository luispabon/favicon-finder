PHP_CONTAINER?="phpdockerio/php73-cli"
XDEBUG_PACKAGE?="php7.3-xdebug"

PHP_RUN=docker run --rm -e XDEBUG_MODE=coverage -v "$(PWD):/workdir" -w "/workdir" --rm $(PHP_CONTAINER)

all: static-analysis coverage-tests mutation-tests

prep-ci:
	$(PHP_RUN) composer -o install

unit-tests:
	$(PHP_RUN) vendor/bin/phpunit --testdox --colors=always

coverage-tests:
	$(PHP_RUN) bash -c " \
		apt update && \
		apt install $(XDEBUG_PACKAGE) && \
		vendor/bin/phpunit --testdox --colors=always"

mutation-tests:
	$(PHP_RUN) vendor/bin/infection --coverage=reports/infection --threads=2 -s --min-msi=95 --min-covered-msi=95

static-analysis:
	$(PHP_RUN) vendor/bin/phpstan -v analyse -l 7 src -c phpstan.neon  && printf "\n ${bold}PHPStan:${normal} static analysis good\n\n" || exit 1
