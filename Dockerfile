FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    bash \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        intl \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Complete composer autoload (skip artisan scripts - they run at startup)
RUN composer dump-autoload --optimize --no-dev --no-scripts

# Install Node dependencies and build assets
RUN npm ci && npm run build && rm -rf node_modules

# Create required directories and set permissions
RUN mkdir -p storage/framework/{views,sessions,cache} \
             storage/logs \
             storage/app/public \
             bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Configure php-fpm to listen on TCP port 9000
RUN sed -i 's|listen = /run/php-fpm/www.sock|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true && \
    sed -i 's|listen = 9000|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/zz-docker.conf 2>/dev/null || true

# PHP config for production (show errors temporarily for debugging)
RUN echo "display_errors=Off" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "log_errors=On" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "max_input_vars=10000" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "max_multipart_body_parts=12000" >> /usr/local/etc/php/conf.d/app.ini

# Configure nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configure supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
