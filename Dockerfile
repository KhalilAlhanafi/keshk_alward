FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (needed for Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /app

# Copy the application code
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader

# Configure PHP Upload Limits and memory
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Ensure storage directories exist and are writable
RUN mkdir -p storage/app/public/products \
    && mkdir -p storage/app/public/categories \
    && mkdir -p storage/app/public/settings \
    && mkdir -p storage/app/public/payment-proofs \
    && chmod -R 775 storage bootstrap/cache

# Install Node dependencies and build Vite assets
RUN npm install && npm run build

# Setup SQLite Database for the demo
RUN touch database/database.sqlite

# We will run migrations in the CMD step against the remote production database.

# Render assigns a dynamic port via the PORT environment variable
# We tell Laravel to serve on this port
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
