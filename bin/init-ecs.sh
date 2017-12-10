#!/bin/sh

echo "Warming cache"
php bin/console cache:warm

echo "Cleaning up"
rm -rf var/cache/build
chown -R www-data:www-data var
