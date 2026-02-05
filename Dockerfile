FROM php:8.2-apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html
COPY www/ /var/www/html/
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
EXPOSE 80