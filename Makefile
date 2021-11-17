PHP_CONTAINER?="phpdockerio/php74-cli"
XDEBUG_PACKAGE?="php7.4-xdebug"

PHP_RUN=docker run --rm -e XDEBUG_MODE=coverage -v "$(PWD):/workdir" -w "/workdir" --rm $(PHP_CONTAINER)

all: static-analysis coverage-tests mutation-tests

prep-ci:
	$(PHP_RUN) composer -o install

coverage-tests:
	php -d zend_extension=xdebug.so vendor/bin/phpunit --testdox

mutation-tests:
	vendor/bin/infection --coverage=reports/infection --threads=2 -s --min-msi=95 --min-covered-msi=95

static-analysis:
	vendor/bin/phpstan -v analyse -l 7 src -c phpstan.neon  && printf "\n ${bold}PHPStan:${normal} static analysis good\n\n" || exit 1
