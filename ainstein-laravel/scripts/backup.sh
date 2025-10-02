#!/bin/bash

# Ainstein Laravel Backup Script
set -e

PROJECT_NAME="ainstein-laravel"
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[BACKUP]${NC} $1"
}

# Create backup directory
mkdir -p $BACKUP_DIR

log_info "Starting backup process..."

# Database backup
log_info "Backing up database..."
docker-compose -f docker-compose.prod.yml exec -T mysql mysqldump \
    -u root -p${DB_ROOT_PASSWORD} \
    --single-transaction \
    --routines \
    --triggers \
    ${DB_DATABASE} > ${BACKUP_DIR}/database_${DATE}.sql

# Application files backup
log_info "Backing up storage files..."
docker-compose -f docker-compose.prod.yml exec -T app tar -czf - \
    -C /var/www/html storage/app | cat > ${BACKUP_DIR}/storage_${DATE}.tar.gz

# Environment backup
log_info "Backing up environment configuration..."
cp .env.production ${BACKUP_DIR}/env_${DATE}.backup

# Clean old backups (keep last 7 days)
log_info "Cleaning old backups..."
find $BACKUP_DIR -name "database_*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +7 -delete
find $BACKUP_DIR -name "env_*.backup" -mtime +7 -delete

log_info "✅ Backup completed successfully!"
log_info "Files saved to: $BACKUP_DIR"
ls -la ${BACKUP_DIR}/*${DATE}*
