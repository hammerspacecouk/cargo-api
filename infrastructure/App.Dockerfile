# ARG BASED REPO won't work until the docker version is updated. Use explict repo for now
FROM 323441517494.dkr.ecr.eu-west-2.amazonaws.com/php-base-image:latest

ENV PROJECT planet-cargo

ARG REPO
ARG TAG=latest
#FROM ${REPO}:${TAG}

ARG ENV=prod
ENV APP_ENV=$ENV

# Setup the application
RUN chown -R www-data:www-data /var/www/.
COPY --chown=www-data:www-data . /var/www

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
    bin/console cache:warm --env=prod

# Ensure the environment is injected on startup
RUN wget -O /tmp/ssm-parent.tar.gz https://github.com/springload/ssm-parent/releases/download/v1.1.2/ssm-parent_1.1.2_linux_amd64.tar.gz && \
    tar xvf /tmp/ssm-parent.tar.gz && mv ssm-parent /sbin/ssm-parent && rm /tmp/ssm-parent.tar.gz

ENTRYPOINT ["/sbin/ssm-parent", "run", "-e", "--plain-path", "/$PROJECT/$ENV/", "-r",  "--"]

CMD ["php-fpm"]

EXPOSE 9000
