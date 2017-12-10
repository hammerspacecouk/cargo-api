ARG REPO
ARG TAG=latest
FROM ${REPO}:${TAG}

ARG ENV=prod
ENV APP_ENV=$ENV

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