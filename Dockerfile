FROM php:8.2-apache

# 1. Install System Dependencies & PostgreSQL + Redis PHP Extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# 2. Configure Apache DocumentRoot to point to Laravel's /public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i '/<Directory \/var\/www\/html\/public>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 3. Enable Apache Rewrite Module
RUN a2enmod rewrite

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Set working directory and copy application source code
WORKDIR /var/www/html
COPY . .

# 6. Install PHP Production Dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Configure Permissions for storage and bootstrap cache directories (create them if missing)
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Expose Apache (80) and Laravel Reverb WebSockets (8080) ports
EXPOSE 80 8080

# 9. Grant execution permission to the deployment startup script
RUN chmod +x /var/www/html/start.sh

# 10. Start the web service using the custom bootstrap script
ENTRYPOINT ["/var/www/html/start.sh"]
