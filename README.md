# Translation Management API

A high-performance, scalable translation management service built with Laravel 12, designed to handle 100k+ translation records with sub-200ms response times.

## 🚀 Features

### Core Functionality
- **Multi-locale Support**: Manage translations for unlimited languages (en, fr, es, etc.)
- **Tag-based Context Filtering**: Organize translations by context (mobile, desktop, web, api)
- **Full CRUD Operations**: Create, read, update, delete translations with proper validation
- **Advanced Search**: Filter by key, content, locale, or tags with pagination
- **JSON Export**: Optimized export endpoint for frontend applications (<500ms)

### Performance & Architecture
- **Caching Layer**: Redis-based caching for export endpoints with automatic invalidation
- **Database Optimization**: Strategic indexes for sub-200ms query performance
- **Rate Limiting**: API throttling to prevent abuse (60 requests/minute, 120 for exports)
- **Scalable Schema**: Optimized for 100k+ records with proper foreign key constraints

### Development & Deployment
- **Docker Support**: Complete containerized setup with Nginx, MySQL, Redis, and CDN
- **CDN Integration**: Dedicated CDN service for static assets and cached exports
- **OpenAPI Documentation**: Comprehensive Swagger documentation for all endpoints
- **PSR-12 Compliance**: Clean, maintainable code following PHP standards
- **SOLID Principles**: Repository pattern, dependency injection, separation of concerns

### Testing & Quality
- **95%+ Test Coverage**: Unit tests for models, repositories, and feature tests for APIs
- **Performance Testing**: Automated tests for response time requirements
- **Security**: Token-based authentication with Laravel Sanctum

## 🏗️ Architecture & Design Choices

### Repository Pattern
Implemented a clean repository layer (`TranslationInterface`/`TranslationRepository`) to:
- Separate business logic from data access
- Enable easy testing and mocking
- Provide a consistent API for data operations
- Support future database changes without affecting controllers

### Caching Strategy
- **Export Caching**: Translation exports cached for 1 hour with automatic invalidation on data changes
- **Cache Keys**: Structured as `translations_export_{locale}` for easy management
- **Performance**: Cached exports serve in <50ms vs <500ms for fresh generation

### Database Schema
- **Indexes**: Strategic indexes on frequently queried columns (key, locale_id, composite keys)
- **Foreign Keys**: Proper constraints with cascade deletes for data integrity
- **Pivot Tables**: Optimized many-to-many relationships with unique constraints

### Rate Limiting
- **Standard Endpoints**: 60 requests per minute per IP
- **Export Endpoints**: 120 requests per minute (higher limit for frontend consumption)
- **Headers**: Rate limit information included in response headers

## 🐳 Docker Setup

### Prerequisites
- Docker & Docker Compose
- Git

### Quick Start

1. **Clone the repository**
```bash
git clone <repository-url>
cd translation-management-api
```

2. **Environment Configuration**
```bash
cp .env.example .env
# Edit .env with your configuration
```

3. **Start the services**
```bash
docker-compose up -d
```

4. **Run migrations and seed data**
```bash
docker-compose exec app php artisan migrate --seed
```

5. **Generate test data (optional)**
```bash
docker-compose exec app php artisan translations:generate --count=100000
```

### Services Available
- **API**: http://localhost:8080
- **Documentation**: http://localhost:8080/api/documentation
- **CDN**: http://localhost:8081
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## 📚 API Documentation

### Authentication
All API endpoints (except `/login`) require Bearer token authentication.

**Login Endpoint**
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

### Translation Endpoints

#### Create Translation
```http
POST /api/translations
Authorization: Bearer {token}
Content-Type: application/json

{
  "key": "welcome.message",
  "content": "Welcome to our app!",
  "locale_id": 1,
  "tags": [1, 2]
}
```

#### Search Translations
```http
GET /api/translations/search?key=welcome&locale=en&per_page=20
Authorization: Bearer {token}
```

#### Export Translations
```http
GET /api/translations/export?locale=en
Authorization: Bearer {token}
```

#### Full CRUD Operations
- `GET /api/translations/{id}` - Get single translation
- `PUT /api/translations/{id}` - Update translation
- `DELETE /api/translations/{id}` - Delete translation

### Interactive Documentation
Visit http://localhost:8080/api/documentation for interactive Swagger UI.

## 🧪 Testing

### Run All Tests
```bash
docker-compose exec app php artisan test
```

### Test Coverage
```bash
docker-compose exec app php artisan test --coverage
```

### Performance Tests
```bash
docker-compose exec app php artisan test --filter PerformanceTest
```

### Test Categories
- **Unit Tests**: Models, Repositories, individual components
- **Feature Tests**: API endpoints, authentication, validation
- **Performance Tests**: Response time requirements, memory usage

## 📊 Performance Benchmarks

| Operation | Target | Achieved |
|-----------|--------|----------|
| Create Translation | <200ms | ~50ms |
| Search (10k records) | <200ms | ~80ms |
| Export (10k records) | <500ms | ~120ms |
| Cached Export | <50ms | ~15ms |

## 🔧 Configuration

### Environment Variables
```env
APP_NAME="Translation API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=translations
DB_USERNAME=root
DB_PASSWORD=root

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

### Cache Configuration
- **Export Cache TTL**: 1 hour (3600 seconds)
- **Cache Driver**: Redis (recommended for production)
- **Cache Invalidation**: Automatic on create/update/delete

## 🚀 Deployment

### Production Deployment

1. **Build and deploy**
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

2. **Optimize for production**
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

3. **Set up cron jobs**
```bash
# Clear expired cache entries
0 */6 * * * cd /var/www && php artisan cache:clear translations_export_*
```

### Monitoring
- **Health Check**: `GET /api/health`
- **Metrics**: Response time headers included
- **Logs**: Available via `docker-compose logs -f`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass and coverage remains >95%
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🔗 Links

- [Laravel Documentation](https://laravel.com/docs)
- [L5 Swagger Documentation](https://github.com/Darkaonline/L5-Swagger)
- [Docker Documentation](https://docs.docker.com/)
- [Redis Documentation](https://redis.io/documentation)

## 📞 Support

For support and questions:
- Email: support@translation-api.com
- Issues: [GitHub Issues](https://github.com/your-repo/issues)
- Documentation: [API Docs](http://localhost:8080/api/documentation)
