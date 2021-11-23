
mkdir -p build/coverage/parts
/usr/bin/php7.4 -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-php build/coverage/parts/php7.4.cov
/usr/bin/php8.0 -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-php build/coverage/parts/php8.0.cov
/usr/bin/php7.4 -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-clover build/coverage/parts/php7.4.xml
/usr/bin/php8.0 -d pcov.enabled=1 ./vendor/bin/phpunit --coverage-clover build/coverage/parts/php8.0.xml
/usr/bin/php8.0 ./vendor/bin/phpcov merge --clover build/coverage/clover.xml build/coverage/parts
