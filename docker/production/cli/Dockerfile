FROM php:8.3-cli

# Параметры пользователя
ARG UID=1000
ARG GID=1000
ARG NODE_VERSION=22

# Установка зависимостей и Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ca-certificates \
    gnupg \
    unzip \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    opcache \
    intl \
    zip \
    bcmath \
    soap \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка Node.js из официального источника (без NVM)
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - && \
    apt-get install -y nodejs && \
    apt-get autoremove -y && apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Создание пользователя без sudo
RUN groupadd -g ${GID} www && \
    useradd -m -u ${UID} -g www -s /bin/bash www

# Переключение на пользователя
USER www

# Рабочая директория
WORKDIR /var/www

COPY docker/production/cli/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
