#!/bin/sh

# We need the TESTFILES var in $1
TESTFILES=$@

for TESTFILE in $TESTFILES; do
    echo $TESTFILE
    docker-compose exec -T fpm ./vendor/bin/phpunit -c app --log-junit var/tests/phpunit/phpunit_$(uuidgen).xml $TESTFILE
done
