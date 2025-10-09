#!/bin/bash

# Production Deployment Script - No Node.js Required
echo "🚀 Deploying Laravel ITSO Helpdesk to Production..."

# Set environment
export APP_ENV=production
export VITE_ENABLED=false

# Copy production environment
cp .env.production .env

# Install Composer dependencies (production)
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
php artisan key:generate --force

# Run database migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink
php artisan storage:link

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

echo "✅ Deployment completed!"
echo "📝 Don't forget to:"
echo "   - Set up your web server (Apache/Nginx)"
echo "   - Configure database connection"
echo "   - Set up SSL certificate"
echo "   - Configure email settings"

echo "🔗 Access your application at: $APP_URL"