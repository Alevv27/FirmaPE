FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
 && docker-php-ext-install curl \
 && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# 🔥 FIX IMPORTANTE PARA APACHE
RUN a2enmod rewrite

RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-available/fir.conf \
 && a2enconf fir

EXPOSE 80
