all: run-phpstan run-unit-tests run-infection

run-unit-tests:
	php -d zend_extension=xdebug.so vendor/bin/phpunit --testdox

run-infection:
	vendor/bin/infection --coverage=reports/infection --threads=2 -s --min-msi=60 --min-covered-msi=60

run-phpstan:
	vendor/bin/phpstan -v analyse -l 1 src -c phpstan.neon  && printf "\n ${bold}PHPStan:${normal} static analysis good\n\n" || exit 1
