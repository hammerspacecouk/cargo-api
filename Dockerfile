FROM php:latest

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH=$PATH:vendor/bin

RUN apt-get update && apt-get install -y --no-install-recommends \
      curl \
      git \
      python-dev \
      python-pip \
      zlib1g-dev

RUN pip install awscli
RUN docker-php-ext-install zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN rm -rf /var/lib/apt/lists/*
