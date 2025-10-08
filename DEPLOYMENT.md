# Ainstein Laravel - Production Deployment Guide

This guide provides step-by-step instructions for deploying Ainstein Laravel to a production environment using Docker.

## Prerequisites

- Docker and Docker Compose installed on your server
- Domain name configured to point to your server
- SSL certificate (handled automatically by Let's Encrypt)
- Minimum 2GB RAM, 1 CPU core, 20GB storage

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <your-repository-url> ainstein-laravel
   cd ainstein-laravel
   ```

2. **Configure environment**
   ```bash
   cp .env.production .env
   # Edit .env with your production settings (see Configuration section)
   ```

3. **Deploy**
   ```bash
   chmod +x scripts/deploy.sh
   ./scripts/deploy.sh
   ```

## Configuration

### Required Environment Variables

Edit `.env.production` and configure the following:

#### Application Settings
```env
APP_NAME="Your Company Name"
APP_URL=https://your-domain.com
APP_KEY=base64:GENERATE_NEW_KEY_HERE  # Will be auto-generated on first deploy
```

#### Database Configuration
```env
DB_DATABASE=ainstein_production
DB_USERNAME=ainstein_user
DB_PASSWORD=your_secure_database_password
DB_ROOT_PASSWORD=your_secure_root_password
```

#### OpenAI Integration
```env
OPENAI_API_KEY=sk-your-openai-api-key
OPENAI_MODEL=gpt-4o
```

#### SSL & Domain Configuration
```env
LETSENCRYPT_EMAIL=admin@your-domain.com
SANCTUM_STATEFUL_DOMAINS=your-domain.com,api.your-domain.com
```

#### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

## Deployment Process

### Manual Deployment

1. **Build and deploy**
   ```bash
   # Build Docker image
   docker build -t ainstein-laravel:latest .
   
   # Load environment variables
   export $(cat .env | grep -v '^#' | xargs)
   
   # Deploy with Docker Compose
   docker-compose -f docker-compose.prod.yml up -d
   ```

2. **Run initial setup**
   ```bash
   # Wait for services to start
   sleep 30
   
   # Run migrations
   docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
   
   # Cache configuration
   docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
   docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
   docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
   ```

### Automated Deployment

Use the provided deployment script:

```bash
./scripts/deploy.sh
```

This script will:
- Validate environment configuration
- Build Docker images
- Deploy services with zero-downtime
- Run database migrations
- Optimize application caches
- Perform health checks

## Monitoring & Maintenance

### Health Checks

The application provides health check endpoints:

- **Public**: `GET /api/health` - Basic health status
- **Authenticated**: `GET /api/v1/utils/health` - Detailed health information

### View Logs

```bash
# View all service logs
docker-compose -f docker-compose.prod.yml logs -f

# View specific service logs
docker-compose -f docker-compose.prod.yml logs -f app
docker-compose -f docker-compose.prod.yml logs -f mysql
docker-compose -f docker-compose.prod.yml logs -f redis
```

### Backups

Run the backup script regularly:

```bash
./scripts/backup.sh
```

Set up automated backups via cron:
```bash
# Add to crontab (crontab -e)
0 2 * * * /path/to/ainstein-laravel/scripts/backup.sh
```

### Updates

1. **Pull latest code**
   ```bash
   git pull origin main
   ```

2. **Deploy updates**
   ```bash
   ./scripts/deploy.sh
   ```

## Security Considerations

### SSL/TLS Configuration

- SSL certificates are automatically managed by Let's Encrypt
- HTTPS redirects are enforced
- Security headers are automatically added

### Database Security

- Database is not exposed to external network
- Strong passwords are required
- Regular backups are automated

### Application Security

- Debug mode is disabled in production
- Sensitive information is never logged
- Rate limiting is configured for API endpoints
- CSRF protection is enabled

## Performance Optimization

### Caching

The application uses Redis for:
- Session storage
- Application cache
- Queue management

### Database

- MySQL is configured with production-optimized settings
- Connection pooling is enabled
- Query optimization is automatic

### Web Server

- Nginx is configured for high performance
- Gzip compression is enabled
- Static file caching is optimized

## Troubleshooting

### Common Issues

1. **Application not accessible**
   ```bash
   # Check if services are running
   docker-compose -f docker-compose.prod.yml ps
   
   # Check service logs
   docker-compose -f docker-compose.prod.yml logs app
   ```

2. **Database connection errors**
   ```bash
   # Check MySQL service
   docker-compose -f docker-compose.prod.yml logs mysql
   
   # Test database connection
   docker-compose -f docker-compose.prod.yml exec mysql mysql -u root -p
   ```

3. **SSL certificate issues**
   ```bash
   # Check Let's Encrypt logs
   docker-compose -f docker-compose.prod.yml logs letsencrypt
   
   # Force certificate renewal
   docker-compose -f docker-compose.prod.yml exec letsencrypt /app/force_renew
   ```

### Support Commands

```bash
# Restart all services
docker-compose -f docker-compose.prod.yml restart

# Update and restart specific service
docker-compose -f docker-compose.prod.yml up -d app

# Access application container
docker-compose -f docker-compose.prod.yml exec app bash

# Run Laravel commands
docker-compose -f docker-compose.prod.yml exec app php artisan <command>
```

## Scaling

### Horizontal Scaling

To scale the application:

```bash
# Scale app containers
docker-compose -f docker-compose.prod.yml up -d --scale app=3
```

### Load Balancing

For multiple instances, consider:
- Load balancer (nginx-proxy handles this automatically)
- Shared storage for uploads
- Database clustering for high availability

## Support

For additional support:
- Check application logs for errors
- Monitor system resources (CPU, memory, disk)
- Review security best practices
- Keep Docker images updated

---

**Important**: Always test deployment process in a staging environment before deploying to production.
