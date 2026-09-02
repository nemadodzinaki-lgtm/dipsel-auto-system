FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.2-apache-bookworm

RUN docker-php-ext-install -j"$(nproc)" pdo_mysql \
    && a2enmod expires headers rewrite

COPY deploy/apache-dipsel.conf /etc/apache2/conf-available/dipsel-security.conf
RUN a2enconf dipsel-security

COPY --from=vendor /app/vendor /var/www/html/vendor
COPY . /var/www/html

RUN mkdir -p \
        /var/www/html/uploads/documents \
        /var/www/html/uploads/logos \
        /var/www/html/uploads/profiles \
        /var/www/html/uploads/sliders \
        /var/www/html/uploads/submissions \
        /var/www/html/uploads/vehicles \
    && chown -R www-data:www-data \
        /var/www/html/assets/uploads \
        /var/www/html/uploads \
    && chmod +x /var/www/html/deploy/docker-entrypoint.sh

ENTRYPOINT ["/var/www/html/deploy/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
