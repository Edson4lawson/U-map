#!/bin/bash
set -e

echo "=== U-Map Backend Entrypoint Started ==="

# Render injecte PORT dynamiquement — configurer Apache pour écouter dessus
PORT="${PORT:-10000}"
echo "Configuring Apache to listen on port $PORT..."

# Modifier le port d'écoute Apache
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80>/:$PORT>/" /etc/apache2/sites-available/000-default.conf
echo "Apache port configured successfully"

# Générer la clé APP si elle n'est pas définie
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:CHANGE_THIS" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force || echo "WARNING: Failed to generate APP_KEY"
fi

# Attendre la base de données PostgreSQL
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-5432}..."
    RETRIES=30
    until nc -z "$DB_HOST" "${DB_PORT:-5432}" || [ $RETRIES -eq 0 ]; do
        echo "Database not ready, retrying... ($RETRIES attempts left)"
        RETRIES=$((RETRIES-1))
        sleep 2
    done
    if [ $RETRIES -eq 0 ]; then
        echo "WARNING: Could not connect to database after 30 attempts, continuing anyway..."
    else
        echo "Database is ready!"
    fi
else
    echo "No DB_HOST configured, skipping database wait"
fi

# Lancer les migrations
echo "Running database migrations..."
php artisan migrate --force || echo "WARNING: Migration failed, continuing..."

# Lier le storage
php artisan storage:link 2>/dev/null || echo "WARNING: storage:link failed, continuing..."

# Optimiser pour la production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration for production..."
    php artisan config:cache || echo "WARNING: config:cache failed, continuing..."
    php artisan route:cache || echo "WARNING: route:cache failed, continuing..."
    php artisan view:cache || echo "WARNING: view:cache failed, continuing..."
    php artisan optimize || echo "WARNING: optimize failed, continuing..."
fi

echo "=== Starting Apache on port $PORT ==="
# Execute the CMD from Dockerfile
exec "$@"
