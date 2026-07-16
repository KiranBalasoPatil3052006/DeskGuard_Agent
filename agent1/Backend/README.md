# DeskGuard Backend

REST API backend for DeskGuard Agent - a lightweight Windows monitoring agent for enterprise endpoint health, inventory, and security metrics.

## Requirements
- PHP 8.3+
- Composer 2.x
- MySQL 8.0
- Node.js 24+ (optional, for frontend assets)

## Installation

```bash
git clone <repo-url> DeskGuard
cd DeskGuard/Backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env` database settings, then:

```bash
php artisan migrate
php artisan serve
```

## Default Login
- Development server runs on http://localhost:8000

## API Documentation
Coming soon.

## Testing
```bash
composer test
```
