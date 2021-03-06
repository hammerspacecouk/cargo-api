# ARG BASED REPO won't work until the docker version is updated. Use explict repo for now
ARG BASE_REPO
ARG TAG=latest
FROM ${BASE_REPO}:${TAG}

ARG APP_VERSION=latest
ENV APP_VERSION=$APP_VERSION
ENV APP_ENV=prod

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./conf/php "$PHP_INI_DIR/conf.d/"

COPY ./conf/phpfpm/app.conf /usr/local/etc/php-fpm.d/zz-app.conf

# Setup the application
COPY . /var/www

WORKDIR /var/www

# Ensure executables and permissions
RUN chmod +x bin/*

# Install deps production, clear some space and warm cache (last step)
RUN composer install --optimize-autoloader --apcu-autoloader --no-dev --prefer-dist --no-scripts --no-suggest --no-progress && \
    composer clear-cache && \
    rm /usr/bin/composer && \
    rm -rf vendor/*/*/tests/ && \
    rm -rf vendor/*/*/Tests/ && \
    rm -rf vendor/*/*/test/ && \
    bin/console cache:warmup --env=prod

RUN chown -R www-data:www-data /var/www

# Ensure the environment is injected on startup
RUN wget -O /tmp/ssm-parent.tar.gz https://github.com/springload/ssm-parent/releases/download/v1.1.2/ssm-parent_1.1.2_linux_amd64.tar.gz && \
    tar xvf /tmp/ssm-parent.tar.gz && mv ssm-parent /sbin/ssm-parent && rm /tmp/ssm-parent.tar.gz

ENTRYPOINT ["/sbin/ssm-parent", "run", "-e", "--plain-path", "/$PARAM_PATH/", "-r",  "--"]

CMD ["php-fpm"]

EXPOSE 9000
