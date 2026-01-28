# Translation Management Service

## Objective
API-driven translation service built with Laravel to manage multilingual content
with high performance and scalability.

## Features
- Multi-locale translations
- Tag-based context filtering
- Fast JSON export for frontend apps
- Token-based authentication
- Scalable DB schema
- 100k+ record support
- Dockerized
- Swagger documentation
- High test coverage

## Setup
```bash
git clone repo-url
cd project
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
