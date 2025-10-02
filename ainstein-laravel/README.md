# Ainstein - AI-Powered Content Generation Platform

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Ainstein is a powerful multi-tenant SaaS platform that leverages AI to generate high-quality, SEO-optimized content. Built with Laravel 11, it provides a comprehensive solution for businesses and content creators who need scalable AI content generation capabilities.

## 🚀 Features

### Core Features
- **AI Content Generation**: Generate articles, blog posts, and marketing copy using OpenAI's GPT models
- **Multi-Tenancy**: Complete tenant isolation with individual databases or shared database with tenant scoping
- **SEO Optimization**: Automatic generation of SEO-friendly titles and meta descriptions
- **Custom Prompts**: Create and manage custom prompt templates for specific content needs
- **Multi-Language Support**: Generate content in multiple languages
- **Queue System**: Background processing for AI content generation
- **Token Management**: Track and manage OpenAI API token usage per tenant

### Admin Features
- **Super Admin Dashboard**: Comprehensive admin panel built with Filament
- **Tenant Management**: Create, manage, and monitor tenant accounts
- **User Management**: Role-based access control with admin, tenant admin, and user roles
- **System Analytics**: Monitor platform usage, token consumption, and performance metrics
- **Platform Settings**: Configure OpenAI API keys, models, and system-wide settings

### API Features
- **RESTful API**: Complete API for all platform features
- **API Authentication**: Laravel Sanctum token-based authentication
- **Rate Limiting**: Configurable API rate limits per user/tenant
- **API Documentation**: Comprehensive API documentation with examples
- **Webhook Support**: Event-driven integrations

### Tenant Dashboard
- **Modern UI**: Responsive design built with Tailwind CSS
- **Content Management**: Manage pages, content generations, and prompts
- **Usage Analytics**: Track token usage, content generation statistics
- **Settings Management**: Configure tenant settings, user profiles, API access

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx or Apache
- **Cache**: Redis (recommended) or Memcached
- **Queue**: Redis, Database, or SQS
- **Composer**: 2.x
- **Node.js**: 18+ (for asset compilation)

## 🛠️ Installation

### Quick Start with Docker

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd ainstein-laravel
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your OpenAI API key and database settings
   ```

3. **Start with Docker:**
   ```bash
   docker-compose up -d
   ```

4. **Setup application:**
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```

5. **Access the application:**
   - **Frontend**: http://localhost:8000
   - **Admin Panel**: http://localhost:8000/admin
   - **API Docs**: http://localhost:8000/api/docs

### Manual Installation

1. **Clone and setup:**
   ```bash
   git clone <repository-url>
   cd ainstein-laravel
   composer install
   cp .env.example .env
   ```

2. **Configure environment:**
   ```bash
   php artisan key:generate
   # Edit .env file with your configuration
   ```

3. **Setup database:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Start development server:**
   ```bash
   php artisan serve
   php artisan queue:work
   ```

## 🏗️ Usage

### Demo Account
Use the demo account to explore the platform:
- **Email**: demo@tenant.com
- **Password**: password

### Super Admin Account
Default super admin credentials:
- **Email**: admin@ainstein.com
- **Password**: password

### API Usage

1. **Authenticate:**
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"demo@tenant.com","password":"password"}'
   ```

2. **Create a page:**
   ```bash
   curl -X POST http://localhost:8000/api/pages \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"url_path":"/blog/example","keyword":"AI content","category":"technology"}'
   ```

3. **Generate content:**
   ```bash
   curl -X POST http://localhost:8000/api/content-generations \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"page_id":"PAGE_ID","prompt_type":"article"}'
   ```

## 🚀 Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

### Quick Deploy
```bash
chmod +x deploy.sh
./deploy.sh
```

## 📚 API Documentation

Complete API documentation is available at `/api/docs` when running.

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Laravel**: The PHP framework that powers this application
- **Filament**: Amazing admin panel package
- **OpenAI**: AI capabilities through their powerful API
- **Tailwind CSS**: Utility-first CSS framework

---

**Built with ❤️ using Laravel, OpenAI, and modern web technologies.**
