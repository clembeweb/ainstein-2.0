#!/bin/bash

# Deployment script for Ainstein Laravel Application
# This script handles the deployment process including database migrations,
# cache clearing, and queue worker management.

set -e

echo "🚀 Starting Ainstein deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env file exists
if [ ! -f .env ]; then
    print_error ".env file not found. Please copy .env.example to .env and configure it."
    exit 1
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" .env; then
    print_warning "APP_KEY not found or not properly set. Generating new key..."
    php artisan key:generate --force
fi

# Install/update Composer dependencies
print_status "Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader --no-dev

# Clear and cache configuration
print_status "Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes
print_status "Clearing and caching routes..."
php artisan route:clear
php artisan route:cache

# Clear and cache views
print_status "Clearing and caching views..."
php artisan view:clear
php artisan view:cache

# Clear application cache
print_status "Clearing application cache..."
php artisan cache:clear

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Seed database if needed
if [ "$1" = "--seed" ]; then
    print_status "Seeding database..."
    php artisan db:seed --force
fi

# Create storage symlink
print_status "Creating storage symlink..."
php artisan storage:link

# Set proper permissions
print_status "Setting file permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Restart queue workers
print_status "Restarting queue workers..."
php artisan queue:restart

# Clear and warm up cache
print_status "Warming up cache..."
php artisan cache:warmup 2>/dev/null || true

# Run optimization commands
print_status "Running optimization..."
php artisan optimize

print_status "✅ Deployment completed successfully!"

# Check if queue workers are running
if pgrep -f "artisan queue:work" > /dev/null; then
    print_status "✅ Queue workers are running"
else
    print_warning "⚠️  Queue workers are not running. You may need to start them manually."
    echo "Run: php artisan queue:work --daemon"
fi

# Show application status
print_status "Application status:"
echo "- Environment: $(php artisan env)"
echo "- Cache status: $(php artisan cache:table --no-interaction 2>/dev/null && echo "Configured" || echo "Not configured")"
echo "- Queue connection: $(php artisan queue:monitor --once 2>/dev/null && echo "Working" || echo "Not working")"

echo ""
echo "🎉 Ainstein is ready to go!"
echo "📖 Access the application at your configured APP_URL"
echo "📊 Admin panel available at /admin"
echo "🔧 API documentation at /api/docs"