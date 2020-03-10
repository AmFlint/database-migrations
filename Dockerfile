FROM php:7.4

# Install and enable PDO and PDO_MYSQL Extensions
RUN docker-php-ext-install pdo pdo_mysql
