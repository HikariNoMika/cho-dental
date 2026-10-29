FROM php:8.3-apache

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    sqlite3 libsqlite3-dev libreoffice \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite zip gd bcmath opcache

# Apache: point document root at Laravel's public/ and enable rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node (for building assets)
COPY --from=node:20-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Install JS dependencies and build assets
COPY package.json package-lock.json* ./
RUN npm ci 2>/dev/null || npm install
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

# Copy the rest of the app
COPY . .

# Ensure storage + database dirs are writable
RUN mkdir -p storage/app/prints storage/app/templates database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Default env for SQLite if none provided
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/var/www/html/database/database.sqlite

EXPOSE 80

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
