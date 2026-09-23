#!/bin/sh
set -e

# Salon Appointment Manager - Docker Entrypoint Script

echo "==> Initializing Salon Appointment Manager environment..."

# Ensure required storage directories exist
mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/fonts \
         /var/www/html/bootstrap/cache

# If .env does not exist, initialize from .env.docker or .env.example
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.docker ]; then
        echo "==> Creating .env from .env.docker..."
        cp /var/www/html/.env.docker /var/www/html/.env
    elif [ -f /var/www/html/.env.example ]; then
        echo "==> Creating .env from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# Ensure composer dependencies are installed
if [ ! -d /var/www/html/vendor ]; then
    echo "==> Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure application key is set
if [ -f /var/www/html/.env ]; then
    if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
        echo "==> Generating application key..."
        php artisan key:generate --force --no-interaction
    fi
fi

# Create storage symlink if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "==> Linking public storage..."
    php artisan storage:link --no-interaction || true
fi

# Wait for MySQL database if host is defined
if [ -n "$DB_HOST" ]; then
    echo "==> Waiting for database connection at $DB_HOST:${DB_PORT:-3306}..."
    max_retries=30
    count=0
    until php -r "
        \$host = getenv('DB_HOST');
        \$port = getenv('DB_PORT') ?: 3306;
        \$db   = getenv('DB_DATABASE');
        \$user = getenv('DB_USERNAME');
        \$pass = getenv('DB_PASSWORD');
        try {
            new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass, [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        count=$((count + 1))
        if [ $count -gt $max_retries ]; then
            echo "==> Warning: Database connection timed out. Proceeding anyway..."
            break
        fi
        sleep 2
    done
    if [ $count -le $max_retries ]; then
        echo "==> Database connection established successfully!"
    fi
fi

echo "==> Ready. Starting command: $@"
exec "$@"
