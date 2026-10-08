FROM php:7.1.33-cli

RUN set -eux; \
    export DEBIAN_FRONTEND=noninteractive; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    printf '%s\n' \
      'deb https://archive.debian.org/debian buster main' \
      'deb https://archive.debian.org/debian-security buster/updates main' \
      > /etc/apt/sources.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip libicu-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql intl; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app/tests
