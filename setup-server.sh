#!/bin/bash
# ==============================================================================
# Kashk Al-Ward (كشك الورد) — Production Server Setup Script for Ubuntu 24.04
# Domain: keshkalward.app
# ==============================================================================

set -e

echo "=================================================="
echo " 🌸 Starting Kashk Al-Ward Server Setup..."
echo "=================================================="

export DEBIAN_FRONTEND=noninteractive

# 1. System Update & Dependencies
echo "--> Updating system packages..."
apt update && apt upgrade -y
apt install -y software-properties-common curl git unzip ufw fail2ban certbot python3-certbot-nginx

# 2. Setup 2GB Swap Memory (for rock-solid stability)
if [ ! -f /swapfile ]; then
    echo "--> Creating 2GB Swap space..."
    fallocate -l 2G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=2048
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo "Swap enabled successfully."
fi

# 3. Install Nginx & MariaDB
echo "--> Installing Nginx & MariaDB..."
apt install -y nginx mariadb-server mariadb-client
systemctl enable nginx
systemctl start nginx
systemctl enable mariadb
systemctl start mariadb

# 4. Install PHP 8.2 & Required Extensions
echo "--> Installing PHP 8.2 & Extensions..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
    php8.2-sqlite3 php8.2-redis

# Configure PHP 8.2 FPM limits
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 25M/" /etc/php/8.2/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 30M/" /etc/php/8.2/fpm/php.ini
sed -i "s/memory_limit = .*/memory_limit = 256M/" /etc/php/8.2/fpm/php.ini
systemctl restart php8.2-fpm

# 5. Install Composer & Node.js
echo "--> Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "--> Installing Node.js (v20 LTS)..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi

# 6. Setup Database
echo "--> Configuring Database 'kashk_al_ward'..."
DB_PASS="KashkWard2026!DbPass"
mariadb -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS kashk_al_ward CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kashk_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON kashk_al_ward.* TO 'kashk_user'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

# 7. Configure Nginx with High-Performance Zero-PHP Static Serving & Browser Caching
echo "--> Configuring Nginx for keshkalward.app..."
cat > /etc/nginx/sites-available/keshkalward.app << 'NGINX_CONF'
server {
    listen 80;
    listen [::]:80;
    server_name keshkalward.app www.keshkalward.app;
    root /var/www/keshkalward/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml font/woff2 font/woff;

    # 1. Zero-PHP Static Storage Delivery (/storage/...)
    # Serves images directly from kernel cache without invoking PHP or touching Database
    location /storage/ {
        alias /var/www/keshkalward/storage/app/public/;
        try_files $uri =404;
        expires 365d;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
        log_not_found off;
    }

    # 2. Immutable 1-Year Caching for Static Assets (Images, CSS, JS, Fonts)
    location ~* \.(?:ico|css|js|gif|jpe?g|png|webp|svg|woff2?|ttf|otf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
        log_not_found off;
        try_files $uri =404;
    }

    # 3. Main Application Routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # 4. FastCGI PHP 8.2 Handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONF

# Enable site
ln -sf /etc/nginx/sites-available/keshkalward.app /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# 8. Setup Application Directory
mkdir -p /var/www/keshkalward
chown -R www-data:www-data /var/www/keshkalward

# 9. Firewall (UFW)
echo "--> Configuring Firewall..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

echo "=================================================="
echo " 🎉 Server environment setup completed successfully!"
echo " Web server: Nginx"
echo " PHP Version: 8.2 FPM"
echo " Database: MariaDB (DB: kashk_al_ward, User: kashk_user)"
echo " Project root: /var/www/keshkalward"
echo "=================================================="
