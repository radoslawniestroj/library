FROM php:8.3-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip libonig-dev curl \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI (opcjonalnie)
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html
