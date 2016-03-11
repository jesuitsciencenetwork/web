#!/usr/bin/env bash

SYMFONY_ENV=prod composer install --no-dev --optimize-autoloader

php console doctrine:schema:update --env=prod --force

php console assetic:dump --env=prod --no-debug
php console clear:cache --env=prod
