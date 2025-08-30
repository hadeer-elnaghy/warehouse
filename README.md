# Warehouse Inventory Management System

A comprehensive RESTful API for managing inventory across multiple warehouses built with Laravel 12.

## Features

- **Multi-warehouse Management**: Create and manage multiple warehouses with detailed information
- **Inventory Management**: Track inventory items with SKU, pricing, and categorization
- **Stock Tracking**: Monitor stock levels, reserved quantities, and available quantities
- **Stock Transfers**: Transfer stock between warehouses with validation and status tracking
- **Low Stock Alerts**: Automatic detection and notification of low stock levels
- **Search & Filtering**: Advanced search and filtering capabilities for inventory items
- **Caching**: Optimized performance with intelligent caching strategies
- **Authentication**: Secure API access with Laravel Sanctum
- **Validation**: Comprehensive input validation and sanitization
- **Testing**: Complete test coverage for all functionality

## Requirements

- PHP 8.2+
- Laravel 12.0+
- MySQL/PostgreSQL/SQLite
- Composer

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd warehouse.dev
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure database in `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warehouse_db
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations:
```bash
php artisan migrate --seed
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Queue Management

To keep the queue system running for processing background jobs and events:

### Option 1: Continuous Queue Worker (Recommended for Development)
```bash
php artisan queue:work
```

### Option 2: Queue Scheduler (Recommended for Production)
```bash
# Keep this running continuously
php artisan schedule:run

# Or add to your crontab for automatic execution
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Option 3: Supervisor (Production)
Create a supervisor configuration to automatically manage queue workers:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/worker.log
```

## License

This project is licensed under the MIT License.
