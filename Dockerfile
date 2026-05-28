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
    '<VirtualHost *:__PORT__>' \
    '    DocumentRoot /var/www/html' \
    '    DirectoryIndex Public/index.php index.php index.html' \
    '' \
    '    <Directory /var/www/html>' \
    '        Options -Indexes +FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '' \
    '        RewriteEngine On' \
    '        RewriteRule ^$ Public/index.php [L]' \
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
    'rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf' \
    'ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load' \
    'if [ -f /etc/apache2/mods-available/mpm_prefork.conf ]; then' \
    '    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf' \
    'fi' \
    '' \
    'sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf' \
    'sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf' \
    '' \
    'ls -la /etc/apache2/mods-enabled/mpm_* || true' \
    'apache2ctl -M 2>/dev/null | grep mpm || true' \
    '' \
    'apache2-foreground' \
    > /usr/local/bin/railway-apache-start

RUN chmod +x /usr/local/bin/railway-apache-start

EXPOSE 8080

CMD ["railway-apache-start"]
