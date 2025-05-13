FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    gnupg \
    ca-certificates

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath
RUN docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype
RUN docker-php-ext-install -j$(nproc) gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy Apache virtual host file
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy custom PHP configuration
COPY docker/php/custom.ini $PHP_INI_DIR/conf.d/

# Increase PHP memory limit
RUN echo "memory_limit = 512M" >> $PHP_INI_DIR/conf.d/docker-php-memlimit.ini

# Install Composer dependencies
RUN composer install --no-interaction --no-dev --prefer-dist

# Install Node.js and npm
ENV NODE_VERSION=20.16.0
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash
ENV NVM_DIR=/root/.nvm
RUN . "$NVM_DIR/nvm.sh" && nvm install ${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm use v${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm alias default v${NODE_VERSION}
ENV PATH="/root/.nvm/versions/node/v${NODE_VERSION}/bin/:${PATH}"

# Install npm dependencies and build assets
RUN npm install && npm run build

# Change ownership of storage and bootstrap/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Ensure storage and bootstrap/cache are writable
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy storage directory to a .dist directory
RUN cp -a /var/www/storage /var/www/storage.dist

# Copy bootstrap/cache directory to a .dist directory
RUN cp -a /var/www/bootstrap/cache /var/www/bootstrap/cache.dist

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set as entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]

# Change the document root to public
ENV APACHE_DOCUMENT_ROOT /var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80 443
CMD ["apache2-foreground"]
