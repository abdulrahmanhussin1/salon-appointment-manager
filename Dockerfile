FROM php:8.2-fpm

# Build arguments for user and group IDs (default to 1000 for standard Linux host user)
ARG UID=1000
ARG GID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libicu-dev \
    default-mysql-client \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Get latest Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create a system user to run Composer and Artisan Commands matching host UID/GID
RUN if ! getent group ${GID} >/dev/null; then groupadd -g ${GID} laravel; fi \
    && if ! id -u ${UID} >/dev/null 2>&1; then useradd -u ${UID} -g ${GID} -m -s /bin/bash laravel; fi

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy entrypoint script
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set working directory
WORKDIR /var/www/html

# Set ownership of working directory to the laravel user
RUN chown -R ${UID}:${GID} /var/www/html

# Expose PHP-FPM port
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["php-fpm"]
