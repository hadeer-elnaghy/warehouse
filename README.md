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

### Database Configuration for Testing

The tests use a separate test database to avoid affecting your development data. You need to configure this properly:

#### Option 1: Create Test Database (Recommended)
```bash
# Create the test database (replace 'your_project_test' with your preferred name)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS your_project_test;"
```

#### Option 2: Use Same Database for Testing
Update your `phpunit.xml` file to use your existing database:
```xml
<env name="DB_DATABASE" value="your_existing_database_name"/>
```

**Note**: If you get "Unknown database" error, either create the test database or update the `phpunit.xml` configuration to match your actual database name.

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
