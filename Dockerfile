FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y unzip git libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
RUN a2enmod mpm_prefork rewrite headers

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

RUN printf '%s\n' \
    '<VirtualHost *:${PORT}>' \
    '    DocumentRoot /var/www/html' \
    '' \
    '    <Directory /var/www/html>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '' \
    '        RewriteEngine On' \
    '' \
    '        RewriteCond %{REQUEST_FILENAME} -f [OR]' \
    '        RewriteCond %{REQUEST_FILENAME} -d' \
    '        RewriteRule ^ - [L]' \
    '' \
    '        RewriteRule ^ Public/index.php [L]' \
    '    </Directory>' \
    '' \
    '    <IfModule mod_headers.c>' \
    '        <FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|woff|woff2|ttf)$">' \
    '            Header set Cache-Control "public, max-age=604800"' \
    '        </FilesMatch>' \
    '    </IfModule>' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    '' \
    ': "${PORT:=8080}"' \
    '' \
    'sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf' \
    'sed -i "s/\${PORT}/${PORT}/g" /etc/apache2/sites-available/000-default.conf' \
    '' \
    'apache2-foreground' \
    > /usr/local/bin/railway-apache-start

RUN chmod +x /usr/local/bin/railway-apache-start

EXPOSE 8080

CMD ["railway-apache-start"]
