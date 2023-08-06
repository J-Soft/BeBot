FROM php:7.4-alpine
RUN docker-php-ext-install pdo pdo_mysql mysqli sockets bcmath

