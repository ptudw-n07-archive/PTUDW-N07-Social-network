FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite headers

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

RUN cat > /etc/apache2/sites-available/000-default.conf <<'APACHE'
<VirtualHost *:80>
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        AllowOverride All
        Require all granted

        RewriteEngine On

        # Serve existing files/directories directly
        RewriteCond %{REQUEST_FILENAME} -f [OR]
        RewriteCond %{REQUEST_FILENAME} -d
        RewriteRule ^ - [L]

        # Clean URL router
        RewriteRule ^ Public/index.php [L]
    </Directory>

    <IfModule mod_headers.c>
        <FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|woff|woff2|ttf)$">
            Header set Cache-Control "public, max-age=604800"
        </FilesMatch>
    </IfModule>
</VirtualHost>
APACHE

EXPOSE 80
