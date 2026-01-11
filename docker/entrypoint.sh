#!/bin/bash

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (force for production)
php artisan migrate --force

# Start Nginx
service nginx start

# Start PHP-FPM
php-fpm
