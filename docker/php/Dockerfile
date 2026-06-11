FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    zstd \
    tar \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY scripts/ensure-dataset.sh /usr/local/bin/ensure-dataset
RUN chmod +x /usr/local/bin/ensure-dataset

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]