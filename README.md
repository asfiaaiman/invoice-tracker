# Invoice Tracker

A comprehensive Laravel-based web application designed to manage multiple agencies and track their invoicing against the Serbian VAT entry threshold (6,000,000 RSD). This application helps businesses monitor their revenue streams, ensure compliance with Serbian tax regulations, and validate business rules across multiple agencies.

## 📋 Project Origin

This project was developed as an implementation of an Upwork job. The original job posting can be found at:
- [Upwork Job Posting](https://www.upwork.com/jobs/~021984247206979591113?referrer_url_path=/nx/search/jobs/details/~021984247206979591113)

## 🎯 Purpose

The Invoice Tracker is an internal management system that allows businesses operating multiple agencies to:

- Track invoices across all agencies from a single interface
- Monitor revenue against the Serbian VAT threshold
- Ensure compliance with business rules (minimum clients, maximum client concentration)
- Generate professional PDF invoices
- Analyze client distribution and revenue patterns
- Manage clients, products, and agencies efficiently

## ✨ Features

### Multi-Agency Management
- Create and manage multiple agencies with complete business information
- Activate/deactivate agencies
- Per-agency settings and configurations
- Agency-specific invoice numbering with customizable prefixes

### Client & Product Management
- Comprehensive client database with contact information
- Product catalog with pricing and descriptions
- Many-to-many relationships: clients and products can be assigned to multiple agencies
- Flexible agency-client and agency-product associations

### Invoice Management
- **Create Invoices**: Generate invoices with multiple line items
- **Per-Agency Numbering**: Automatic invoice number generation per agency with custom prefixes
- **PDF Generation**: Professional PDF invoice downloads using DomPDF
- **Advanced Filtering**: Filter invoices by agency, date range, client, or search terms
- **Soft Delete**: Safely delete invoices with soft delete support
- **Invoice Editing**: Update existing invoices while maintaining data integrity
- **Date Management**: Issue dates and due dates with proper validation

### Reporting Module
- **Dashboard Overview**: Comprehensive statistics and metrics
- **Current Year Turnover**: Calculate revenue from January 1st to today
- **Last 365 Days Turnover**: Rolling 12-month revenue calculation
- **VAT Threshold Tracking**: Monitor remaining amount until 6,000,000 RSD threshold
- **Client Structure Analysis**: Detailed breakdown of revenue by client
- **Business Rules Validation**:
  - Minimum 5 different clients per agency validation
  - Maximum 70% turnover from single client validation
  - Real-time warnings and alerts for rule violations
- **Period Reports**: Custom date range reporting capabilities

### Authentication & Security
- Laravel Fortify integration for authentication
- User registration and login
- Email verification
- Password reset functionality
- Two-factor authentication (2FA) support
- Rate limiting for login attempts
- Session-based authentication

### Application Settings
- Global application configuration
- Customizable settings per agency
- Invoice prefix management

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12 (latest version)
- **PHP**: PHP 8.2+ (tested with PHP 8.4)
- **Architecture**: Action-based architecture with Service classes
- **Database**: MySQL/MariaDB (SQLite for development)
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Authentication**: Laravel Fortify
- **Testing**: Pest PHP (with Laravel plugin)

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Routing**: Inertia.js for seamless SPA experience
- **Language**: TypeScript
- **Styling**: Tailwind CSS 4
- **UI Components**: reka-ui component library
- **Icons**: lucide-vue-next
- **Build Tool**: Vite 7
- **State Management**: Vue composables

### Development Tools
- **Code Quality**: Laravel Pint (PHP), ESLint + Prettier (JavaScript/TypeScript)
- **Package Management**: Composer (PHP), npm (Node.js)
- **Version Control**: Git
- **CI/CD**: GitHub Actions

## 📋 Requirements

### Server Requirements
- PHP 8.2 or higher
- Composer
- Node.js 22+ and npm
- MySQL 5.7+ / MariaDB 10.3+ or SQLite 3
- Web server (Apache/Nginx) or PHP built-in server

### Local Development (Recommended)
- **Laravel Herd** (for macOS/Windows) - Automatically configures PHP, Composer, and local domain routing
- Alternative: Laravel Sail (Docker)

## 🚀 Installation

### Prerequisites
Ensure you have Composer and Node.js installed on your system.

### Step-by-Step Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd invoice-tracker
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Edit `.env` and configure your database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=invoice_tracker
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

   **For Laravel Herd users**: The application will be available at `http://invoice-tracker.test` automatically.

5. **Database Setup**
   ```bash
   php artisan migrate
   ```

6. **Seed Test Data** (Optional)
   ```bash
   php artisan db:seed
   ```
   
   This creates:
   - 1 test user (email: `test@example.com`, password: `password`)
   - 3 agencies
   - 6 clients (distributed across agencies)
   - 5 products (assigned to all agencies)
   - 5 sample invoices

7. **Build Frontend Assets**
   ```bash
   npm run build
   ```

8. **Start Development Server**
   ```bash
   composer dev
   ```
   
   This runs:
   - Laravel development server
   - Vite development server (with HMR)
   - Queue worker
   
   Or start separately:
   ```bash
   php artisan serve
   npm run dev
   ```

## 🧪 Testing

The application uses **Pest PHP** for testing, providing a modern and expressive testing experience.

### Running Tests
```bash
# Run all tests
composer test
# or
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test file
./vendor/bin/pest tests/Feature/ProductModuleTest.php
```

### Test Structure
- **Feature Tests**: Located in `tests/Feature/` - Test complete user workflows
- **Unit Tests**: Located in `tests/Unit/` - Test individual classes and methods

### Test Database
Tests use a separate database configuration (defined in `phpunit.xml`). Ensure your test database exists:
```bash
mysql -u root -e "CREATE DATABASE invoice_tracker_test;"
```

## 📁 Project Structure

### Backend Architecture

```
app/
├── Actions/              # Single-responsibility action classes
│   ├── Agency/          # Agency-related actions
│   ├── Client/          # Client-related actions
│   ├── Invoice/         # Invoice-related actions
│   ├── Product/         # Product-related actions
│   ├── Report/          # Report generation actions
│   └── Settings/        # Settings management actions
├── DTOs/                # Data Transfer Objects
├── Events/              # Event classes (InvoiceCreated, InvoiceUpdated, etc.)
├── Http/
│   ├── Controllers/     # Slim controllers (delegate to Actions/Services)
│   ├── Middleware/      # Custom middleware
│   └── Requests/        # Form Request validation classes
├── Models/              # Eloquent models with observers
├── Providers/           # Service providers (AppServiceProvider, FortifyServiceProvider)
└── Services/            # Business logic services (InvoiceService, ReportService)
```

### Frontend Architecture

```
resources/js/
├── actions/              # TypeScript action definitions
├── components/           # Reusable Vue components
├── composables/          # Vue composables for shared logic
├── layouts/              # Layout components
├── pages/                # Inertia.js page components
├── routes/               # Route definitions
├── types/                # TypeScript type definitions
└── app.ts                # Application entry point
```

### Key Design Patterns

- **Action Pattern**: Single-purpose action classes for business operations
- **Service Pattern**: Complex business logic encapsulated in service classes
- **DTO Pattern**: Data Transfer Objects for structured data passing
- **Form Requests**: Request validation separated into dedicated classes
- **Observers**: Model lifecycle hooks using Laravel 11+ attribute-based observers

## 🔌 API Routes

### Public Routes
- `GET /` - Welcome page

### Protected Routes (Require Authentication)

#### Dashboard
- `GET /dashboard` - Main dashboard with statistics

#### Agencies
- `GET /agencies` - List all agencies
- `GET /agencies/create` - Create new agency form
- `POST /agencies` - Store new agency
- `GET /agencies/{id}` - Show agency details
- `GET /agencies/{id}/edit` - Edit agency form
- `PUT /agencies/{id}` - Update agency
- `DELETE /agencies/{id}` - Delete agency

#### Clients
- `GET /clients` - List all clients
- `GET /clients/create` - Create new client form
- `POST /clients` - Store new client (with agency assignments)
- `GET /clients/{id}` - Show client details
- `GET /clients/{id}/edit` - Edit client form
- `PUT /clients/{id}` - Update client
- `DELETE /clients/{id}` - Delete client

#### Products
- `GET /products` - List all products
- `GET /products/create` - Create new product form
- `POST /products` - Store new product (with agency assignments)
- `GET /products/{id}` - Show product details
- `GET /products/{id}/edit` - Edit product form
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product

#### Invoices
- `GET /invoices` - List all invoices (with filtering)
- `GET /invoices/create` - Create new invoice form
- `POST /invoices` - Store new invoice
- `GET /invoices/{id}` - Show invoice details
- `GET /invoices/{id}/edit` - Edit invoice form
- `PUT /invoices/{id}` - Update invoice
- `DELETE /invoices/{id}` - Soft delete invoice
- `GET /invoices/{id}/pdf` - Download PDF invoice

#### Reports
- `GET /reports` - List all agencies for reporting
- `GET /reports/period` - Custom period report
- `GET /reports/{agency}` - Show detailed agency report with business rules validation

#### Settings
- `GET /settings/application` - Application settings page
- `POST /settings/application` - Update application settings

#### API Endpoints
- `GET /api/clients?agency_id={id}` - Get clients filtered by agency
- `GET /api/products?agency_id={id}` - Get products filtered by agency

## 🗄️ Database Schema

### Tables

#### `users`
- Standard Laravel user authentication table
- Includes two-factor authentication columns

#### `agencies`
- `id` - Primary key
- `name` - Agency name
- `tax_id` - Tax identification number
- `address` - Street address
- `city` - City
- `zip_code` - Postal code
- `country` - Country
- `is_active` - Active status flag
- `timestamps`

#### `clients`
- `id` - Primary key
- `name` - Client company name
- `tax_id` - Tax identification number
- `address` - Street address
- `city` - City
- `zip_code` - Postal code
- `country` - Country
- `email` - Contact email (nullable)
- `phone` - Contact phone (nullable)
- `timestamps`

#### `products`
- `id` - Primary key
- `name` - Product name
- `description` - Product description (nullable)
- `price` - Default price
- `unit` - Unit of measurement (e.g., "hour", "piece")
- `timestamps`

#### `agency_client` (Pivot)
- `agency_id` - Foreign key to agencies
- `client_id` - Foreign key to clients

#### `agency_product` (Pivot)
- `agency_id` - Foreign key to agencies
- `product_id` - Foreign key to products
- `price` - Agency-specific product price

#### `invoices`
- `id` - Primary key
- `agency_id` - Foreign key to agencies
- `client_id` - Foreign key to clients
- `invoice_number` - Unique invoice number (per agency)
- `issue_date` - Invoice issue date
- `due_date` - Invoice due date
- `subtotal` - Subtotal amount
- `tax` - Tax amount
- `total` - Total amount
- `deleted_at` - Soft delete timestamp (nullable)
- `timestamps`

#### `invoice_items`
- `id` - Primary key
- `invoice_id` - Foreign key to invoices
- `product_id` - Foreign key to products
- `quantity` - Quantity
- `unit_price` - Unit price
- `subtotal` - Line item subtotal
- `timestamps`

#### `settings`
- `id` - Primary key
- `agency_id` - Foreign key to agencies (nullable for global settings)
- `key` - Setting key
- `value` - Setting value (JSON)
- `timestamps`

## 📊 Business Rules

### Minimum Clients Rule
Each agency must invoice at least **5 different clients** within the measurement period (last 365 days). This ensures business diversification and compliance with Serbian tax regulations.

**Validation**: The reporting module checks the number of unique clients that have received invoices in the last 365 days and displays a warning if the count is below 5.

### Maximum Client Share Rule
No single client may exceed **70% of an agency's turnover** in the measured period (last 365 days). This prevents over-reliance on a single client and ensures business stability.

**Validation**: The reporting module calculates each client's share of the total turnover and displays warnings for clients exceeding the 70% threshold.

### Implementation
These rules are validated in:
- `app/Actions/Report/GenerateAgencyReportAction.php`
- `app/Services/ReportService.php`
- Displayed in the frontend at `resources/js/pages/Reports/Period.vue`

## 💰 VAT Threshold

The Serbian VAT entry threshold is set to **6,000,000 RSD**. The application tracks:

1. **Current Year Turnover**: Total revenue from January 1st of the current year to today
2. **Last 365 Days Turnover**: Rolling 12-month revenue calculation
3. **Remaining Amount**: Calculates how much can still be invoiced before reaching the threshold

Both metrics are displayed in the reporting interface to help businesses make informed decisions about their invoicing.

## 🔐 Authentication Features

### Available Features
- ✅ User Registration
- ✅ Login/Logout
- ✅ Email Verification
- ✅ Password Reset
- ✅ Two-Factor Authentication (2FA)
- ✅ Rate Limiting (5 attempts per minute)

### Default Test User
After seeding:
- **Email**: `test@example.com`
- **Password**: `password`

### Two-Factor Authentication
2FA is enabled and can be configured in the user settings. Users with 2FA enabled will be prompted for a code after logging in with their password.

## 🎨 Development Workflow

### Code Style
- **PHP**: Follows PSR-12 coding standards, enforced by Laravel Pint
- **JavaScript/TypeScript**: ESLint + Prettier with Vue and TypeScript plugins

### Running Code Style Checks
```bash
# PHP
./vendor/bin/pint

# JavaScript/TypeScript
npm run lint
npm run format:check
```

### Fixing Code Style Issues
```bash
# PHP
./vendor/bin/pint

# JavaScript/TypeScript
npm run lint  # Auto-fixes issues
npm run format  # Formats code
```

### Database Migrations
```bash
# Create new migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drops all tables and re-runs)
php artisan migrate:fresh --seed
```

### Creating New Features
1. Create migration for database changes
2. Create/update Eloquent models
3. Create Action classes for business logic
4. Create Form Request classes for validation
5. Create Controller methods (delegate to Actions)
6. Create Vue components/pages for frontend
7. Add routes in `routes/web.php`
8. Write tests

## 🔄 CI/CD

The project includes GitHub Actions workflows for:

### Automated Testing
- Runs on push to `main` or `develop` branches
- Runs on pull requests
- Tests with PHP 8.4 and Node.js 22
- Runs full test suite using Pest

### Workflow Files
- `.github/workflows/tests.yml` - Test automation
- `.github/workflows/lint.yml` - Code quality checks

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
- Verify database credentials in `.env`
- Ensure database exists: `php artisan migrate:status`
- Check database server is running

#### Assets Not Loading
```bash
npm run build  # Rebuild assets
# or for development
npm run dev
```

#### Permission Issues
```bash
# Ensure storage and cache directories are writable
chmod -R 775 storage bootstrap/cache
```

#### Composer Dependency Issues
```bash
composer clear-cache
composer install --no-interaction
```

#### Node Dependency Issues
```bash
rm -rf node_modules package-lock.json
npm install
```

## 📝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer test`)
5. Run code style checks
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Code Standards
- Follow PSR-12 for PHP code
- Use TypeScript for all JavaScript code
- Write tests for new features
- Update documentation as needed
- Keep controllers slim, use Actions and Services

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- Frontend powered by [Vue.js](https://vuejs.org) and [Inertia.js](https://inertiajs.com)
- UI components from [reka-ui](https://rekajs.dev)
- Styling with [Tailwind CSS](https://tailwindcss.com)

## 📞 Support

For issues, questions, or contributions, please open an issue on the GitHub repository.

---

**Last Updated**: 2025
**Version**: 1.0.0
