#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo rm -rf "$DIR/../var/cache/prod"

SYMFONY_ENV=prod composer install --no-dev --optimize-autoloader

php $DIR/console doctrine:schema:update --env=prod --force

php $DIR/console assetic:dump --env=prod --no-debug
php $DIR/console cache:clear --env=prod
