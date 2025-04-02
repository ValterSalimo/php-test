FROM php:7.4-fpm-alpine

# Install dependencies
RUN apk add --no-cache postgresql-dev nginx supervisor git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configure supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /server/http

# Create necessary directories
RUN mkdir -p /server/http/web /server/http/src /server/http/config /server/http/vendor /server/http/tests /server/http/logs

# Copy composer files first
COPY composer.json ./

# Install project dependencies explicitly
RUN composer require vlucas/phpdotenv:^5.3 && \
    composer require firebase/php-jwt:^5.2 && \
    composer require symfony/validator:^5.4 && \
    composer require psr/log:^1.1 && \
    composer require symfony/http-foundation:^5.4 && \
    composer install --no-dev --optimize-autoloader

# Copy all project files
COPY . /server/http

# Make scripts executable
RUN chmod +x /server/http/web/*.php
RUN chmod -R 777 /server/http/logs

# Expose port
EXPOSE 80

# Start services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
