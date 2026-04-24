#!/bin/bash
# entrypoint.sh

export PGPASSWORD=$DB_PASSWORD

echo "--- ENTRYPOINT ---"
echo "Waiting for database: $DB_DATABASE on host $DB_HOST..."

# Wait for the database server to be ready
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" > /dev/null 2>&1; do
    echo "Database server is not ready yet... (retrying in 1 second)"
    sleep 1
done

until psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1; do
    echo "Database $DB_DATABASE is still initializing..."
    sleep 1
done

echo "Database $DB_DATABASE is ready!"


# Run Laravel migrations
echo "Running migrations for production database..."
php artisan migrate --force


unset PGPASSWORD

echo "--- END OF ENTRYPOINT, STARTING APPLICATION ---"

# Finally, execute the main process
exec "$@"