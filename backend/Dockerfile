FROM php:8.2.29-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

COPY . .

RUN composer dump-autoload --no-dev --optimize \
   && chown -R www-data:www-data storage bootstrap/cache \
   && printf '%s\n' \
      '<VirtualHost *:80>' \
       '    DocumentRoot /var/www/html/public' \
       '    <Directory /var/www/html/public>' \
       '        AllowOverride All' \
       '        Require all granted' \
       '    </Directory>' \
       '</VirtualHost>' \
       > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
