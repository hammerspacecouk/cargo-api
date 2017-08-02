#!/usr/bin/env bash

# Clear the app cache
php /var/www/api.www.planetcargo.live/app/console cache:clear --env=prod
php /var/www/api.www.planetcargo.live/app/console cache:warm --env=prod

sudo service php-fpm restart
sudo service nginx restart