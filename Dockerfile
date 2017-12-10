FROM 323441517494.dkr.ecr.eu-west-2.amazonaws.com/planet-cargo-base-php:latest

ARG env=prod
ENV APP_ENV=$env

# Setup the application
COPY ./nginx /etc/nginx/conf.d/
COPY . /var/www

WORKDIR /var/www

# Ensure executables and permissions
RUN chmod +x bin/*

# Install deps production
RUN composer install --optimize-autoloader --no-dev --prefer-dist

# Allow to volume to share
VOLUME /var/www /etc/nginx/conf.d

CMD ["php-fpm"]

EXPOSE 9000