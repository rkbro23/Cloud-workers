# Official PHP image with Apache server
FROM php:8.2-apache

# Enable Apache mod_rewrite (agar future me custom routing karni ho)
RUN a2enmod rewrite

# Apne GitHub repo ka saara code container ke web folder me copy karna
COPY . /var/www/html/

# Permissions set karna taaki Apache files ko aaram se read kar sake
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Render ke liye default web port expose karna
EXPOSE 80
