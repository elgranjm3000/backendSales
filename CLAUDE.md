# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Chrystal API** - Laravel 11 quotation/sales management system with multi-role access control, subscription management, and bi-directional synchronization with an external PostgreSQL system (accesos).

## Common Commands

```bash
# Development server
php artisan serve

# Database operations
php artisan migrate:status
php artisan migrate:fresh --seed
php artisan db:seed

# Testing
php artisan test
php artisan test --filter=QuoteTest
php artisan test --coverage

# Code quality
./vendor/bin/pint --test

# Queue and scheduled tasks
php artisan queue:work
php artisan schedule:list
php artisan sessions:cleanup --hours=1

# Cache
php artisan config:clear
php artisan route:list --path=api
php artisan route:cache
```

## Architecture

### Multi-Database System

The app connects to two databases:
- **MySQL (default)**: Main application data (companies, users, products, quotes, customers, sellers)
- **PostgreSQL (`pgsql_pgsql`)**: External accesos system (legacy/reference data)

Database connections configured in `config/database.php` under `pgsql_pgsql` key.

### Role-Based Access Control

**User Roles** (`App\Enums\UserRole`):
- `ADMIN` - Full system access, manages accesos and all users
- `MANAGER` - Supervises companies and sellers, manages users
- `COMPANY` - Owns a company, manages sellers within it
- `SELLER` - Creates quotes, manages customers (scoped to assigned companies)
- `CAJERO` - Point-of-sale role (limited access)

**Middleware** (registered in `bootstrap/app.php`):
- `admin` - `CheckCajeroRole` (check cajero role)
- `manager` - `CheckManagerRole`
- `admin.or.manager` - `CheckAdminOrManagerRole`
- `subscription` - `CheckSubscription` (verifies active subscription with required features)
- `check.active.session` - `CheckActiveSession`
- `auth.api` - `HandleApiAuth`
- `auth.acceso` - `AuthenticateAccesoToken` (API key auth for external clients)
- `throttle.sync` - `ThrottleSyncRequests`
- `throttle.acceso` - `ThrottleAccesoSync`

### Subscription System with Features

**Subscription Features** (enable/disable functionality):
- `sync_products` - Product synchronization
- `sync_customers` - Customer synchronization  
- `sync_sellers` - Seller synchronization
- `sync_categories` - Category synchronization
- `sync_quotes` - Quote synchronization (NOT available in trial)
- `manage_companies` - Company management

Subscription checks via middleware: `CheckSubscription:feature_name`

### Quote Workflow

**Quote Status** (`App\Enums\QuoteStatus`):
- `DRAFT` → `SENT` → `APPROVED` / `REJECTED`
- Auto-transitions to `EXPIRED` when past `valid_until` date

**Quote Triggers**: Database triggers auto-update quote totals when items/tax/discount change (`2025_09_01_194410_create_quote_triggers.php`)

### Model Relationships

**User** hasMany:
- `companies()` - User as company owner
- `sellers()` - User as seller profile
- `subscriptions()` - Active/inactive subscriptions
- `quotes()` - Quotes where user is the seller

**Company** hasMany:
- `products`, `customers`, `sellers`, `quotes`

**Seller** belongsTo:
- `user` (User model)
- `company` (Company model)
- Fields: `code`, `description`, `percent_sales`, `percent_receivable`, `mobilecheck`

**Quote** belongsTo:
- `customer`, `company`, `seller` (User via `user_seller_id`)
- hasMany: `items` (QuoteItem)
- **Note**: To get Seller fields like `code` with quotes, use relationship with both user_id and company_id match

**Product** belongsTo:
- `category`, `company`
- Unique on: (`company_id`, `code`)

**Customer** belongsTo:
- `company`
- Unique on: (`company_id`, `codigo`)

## API Route Structure

### Authentication
- `POST /api/auth/login` - Sanctum token auth
- `POST /api/auth/logout` - Revoke token
- `GET /api/auth/me` - Current user

### CRUD Endpoints (require `auth:sanctum` + subscription)
- `/api/products`, `/api/customers`, `/api/quotes`, `/api/sellers`, `/api/categories`, `/api/companies`, `/api/users`

### Sync Endpoints

**Batch Sync** (`/api/sync-batch/*`):
- GET/POST/DELETE for products, customers, categories, sellers, quotes
- `GET /api/sync-batch/history` - Sync logs
- `GET /api/sync-batch/last-sync` - Last sync per entity

**Client Sync** (`/api/sync-client/*`):
- Uses `auth.acceso` (API key from accesos table)
- `throttle.acceso:100,1` rate limit
- `/batch/*` equivalents to sync-batch routes

**Bi-directional Sync** (`/api/sync-v2/*`):
- PostgreSQL ↔ MySQL sync queues
- `throttle.sync:10,1` rate limit

### Dashboard
- `GET /api/dashboard` - Role-based dashboard metrics

## Key Services

- `QuoteCalculatorService` - Auto-calculates quote totals, tax, discounts
- `SeniatService` - Venezuelan tax/RIF validation
- `CaptchaSolverService` - CAPTCHA solving for forms

## Scheduled Tasks (routes/console.php)

- `sessions:cleanup --hours=1` - Every 15 min (active session cleanup)
- `sessions:cleanup --hours=48` - Daily at 3 AM (deep cleanup)
- `sync:run-all --only-changes` - Every 30 min (pending sync only)
- `sync:run-all --force` - Every 6 hours (full sync)

## Admin Panel (Web Routes)

- `/login` - Admin login form
- `/admin/accesos` - Manage accesos (admin/manager only)
- `/admin/usuarios` - Manage users (manager only)
- `/admin/docs` - API documentation (manager only)

## Testing

- PHPUnit configured in `phpunit.xml`
- SQLite in-memory for tests
- Test files in `tests/Feature/` and `tests/Unit/`

## Important Notes

- **Quote Seller Data**: Quote's `seller()` relationship returns User model. To access Seller fields like `code`, you need a separate relationship matching both `user_id` and `company_id`.
- **Subscription Checks**: Most endpoints require both `auth:sanctum` AND `CheckSubscription` with specific feature.
- **Acceso Table**: PostgreSQL table used for external client authentication via API key (`api_key` column added via migration).
- **Mobile Check**: Sellers have `mobilecheck` field toggled via admin panel to enable/disable mobile access.
