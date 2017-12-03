#!/bin/sh

echo "Fetching parameters"
php /var/www/bin/parameters.php > /var/www/.env
echo "Parameters fetched"
