#!/bin/bash

# Ainstein Laravel Production Deployment Script
set -e

echo "🚀 Starting Ainstein Laravel deployment..."

# Configuration
PROJECT_NAME="ainstein-laravel"
DOCKER_IMAGE="ainstein-laravel:latest"
COMPOSE_FILE="docker-compose.prod.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env.production exists
if [ ! -f ".env.production" ]; then
    log_error ".env.production file not found!"
    log_info "Please copy .env.production template and configure it for your environment"
    exit 1
fi

# Check if required environment variables are set
check_env_vars() {
    local required_vars=(
        "DB_DATABASE"
        "DB_USERNAME" 
        "DB_PASSWORD"
        "DB_ROOT_PASSWORD"
        "OPENAI_API_KEY"
        "LETSENCRYPT_EMAIL"
    )
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^${var}=" .env.production || grep -q "^${var}=your_" .env.production; then
            log_error "Required environment variable ${var} not configured in .env.production"
            exit 1
        fi
    done
}

log_info "Checking environment configuration..."
check_env_vars

# Build the Docker image
log_info "Building Docker image..."
docker build -t $DOCKER_IMAGE .

# Load environment variables
log_info "Loading production environment..."
export $(cat .env.production | grep -v '^#' | xargs)

# Generate application key if needed
if grep -q "APP_KEY=base64:GENERATE_NEW_KEY_HERE" .env.production; then
    log_warn "Generating new application key..."
    NEW_KEY=$(docker run --rm $DOCKER_IMAGE php artisan key:generate --show)
    sed -i "s|APP_KEY=base64:GENERATE_NEW_KEY_HERE|APP_KEY=$NEW_KEY|" .env.production
    log_info "Application key generated and saved to .env.production"
fi

# Stop existing containers
log_info "Stopping existing containers..."
docker-compose -f $COMPOSE_FILE down --remove-orphans || true

# Start new containers
log_info "Starting new containers..."
docker-compose -f $COMPOSE_FILE up -d

# Wait for database to be ready
log_info "Waiting for database to be ready..."
until docker-compose -f $COMPOSE_FILE exec -T mysql mysqladmin ping -h"localhost" --silent; do
    echo -n "."
    sleep 2
done
echo ""

# Run migrations
log_info "Running database migrations..."
docker-compose -f $COMPOSE_FILE exec -T app php artisan migrate --force

# Clear and cache config
log_info "Optimizing application..."
docker-compose -f $COMPOSE_FILE exec -T app php artisan config:cache
docker-compose -f $COMPOSE_FILE exec -T app php artisan route:cache
docker-compose -f $COMPOSE_FILE exec -T app php artisan view:cache

# Test the deployment
log_info "Testing deployment..."
sleep 10
if curl -f http://localhost/api/health >/dev/null 2>&1; then
    log_info "✅ Deployment successful! Application is running."
else
    log_error "❌ Deployment failed! Health check failed."
    log_info "Check logs with: docker-compose -f $COMPOSE_FILE logs"
    exit 1
fi

log_info "🎉 Deployment completed successfully!"
log_info "Your application is now running at: $APP_URL"
log_info "Monitor with: docker-compose -f $COMPOSE_FILE logs -f"
