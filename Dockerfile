FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    curl \
    && docker-php-ext-install pdo pdo_sqlite zip

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

# Install Node dependencies and build Vite assets
RUN npm install && npm run build

# Setup SQLite Database for the demo
RUN touch database/database.sqlite

# Run migrations (create tables)
RUN php artisan migrate --force

# Render assigns a dynamic port via the PORT environment variable
# We tell Laravel to serve on this port
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
