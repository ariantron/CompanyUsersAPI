#!/bin/sh
# Exit immediately if a command exits with a non-zero status
set -e

# Install PHP dependencies using Composer
composer install

# Remove all previous migrations
rm -rf migrations/*

# Create a new migration based on the current database schema
php bin/console make:migration --no-interaction

# Apply the migrations to the database
php bin/console doctrine:migrations:migrate --no-interaction

# Load data fixtures into the database
php bin/console doctrine:fixtures:load --no-interaction

# Start the PHP built-in web server
php -S 0.0.0.0:8000 -t ./public
