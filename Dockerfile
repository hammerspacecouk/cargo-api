# ARG BASED REPO won't work until the docker version is updated. Use explict repo for now
FROM 226925275209.dkr.ecr.us-east-1.amazonaws.com/php-base-image:latest

# todo - USER command for www-data. Can I haz non-priviledged containers?

ARG REPO
ARG TAG=latest
#FROM ${REPO}:${TAG}

ARG ENV=prod
ENV APP_ENV=$ENV

# Setup the application (todo -  --chown=www-data:www-data)
COPY ./nginx /etc/nginx/conf.d/
COPY . /var/www

WORKDIR /var/www

# Ensure executables and permissions
RUN chmod +x bin/*

# Install deps production
RUN composer install --optimize-autoloader --no-dev --prefer-dist --no-scripts

# todo cache warm

# todo - remove composer

# Allow to volume to share
VOLUME /var/www /etc/nginx/conf.d

CMD ["php-fpm"]

EXPOSE 9000
