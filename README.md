# inoERP

[![CI Pipeline](https://github.com/tim777z/inoERP/workflows/CI%20Pipeline/badge.svg)](https://github.com/tim777z/inoERP/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%203.0-orange.svg)](LICENSE.txt)
[![Code Style](https://img.shields.io/badge/Code%20Style-PSR--12-green.svg)](https://www.php-fig.org/psr/psr-12/)

inoERP is an open-source Enterprise Resource Planning (ERP) system built with PHP and MySQL. It provides comprehensive business management modules including finance, inventory, manufacturing, HR, CRM, and more.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [Testing](#testing)
- [Static Analysis](#static-analysis)
- [Code Style](#code-style)
- [Security](#security)
- [Docker](#docker)
- [Contributing](#contributing)
- [License](#license)

## Features

### Core Modules
- **Financial Management** (GL, AP, AR, FA, CST)
- **Supply Chain** (INV, PO, SD, WIP, BOM)
- **Manufacturing** (WIP, BOM, Routing, MRP)
- **Human Resources** (HR, Payroll, Leave, Recruitment)
- **CRM** (SD, Leads, Opportunities, Quotes)
- **Project Management** (PRJ, Tasks, Timesheets)
- **Quality Management** (QA, Inspections, Non-conformances)
- **Asset Management** (AM, Maintenance, Calibration)

### Technical Features
- Multi-organization / Multi-company support
- Role-based access control (RBAC)
- Multi-language / i18n support
- Flexible reporting engine
- Workflow / approval engine
- RESTful API endpoints
- Scheduled jobs / cron integration
- Document management
- Barcode/QR code support

## Architecture

```
inoERP/
├── inoerp/                    # Main application
│   ├── includes/              # Core libraries
│   │   ├── basics/            # Bootstrap & settings
│   │   ├── functions/         # Global functions
│   │   ├── general_class/     # Base classes (DB, Forms, etc.)
│   │   ├── json/              # AJAX/JSON endpoints
│   │   ├── template/          # View templates
│   │   ├── interface/         # Module interfaces
│   │   ├── router/            # URL routing
│   │   └── js/                # JavaScript utilities
│   ├── modules/               # Business modules (GL, INV, HR, etc.)
│   ├── extensions/            # Cross-cutting extensions
│   │   ├── user/              # User management
│   │   ├── role_access/       # RBAC
│   │   ├── file/              # File management
│   │   ├── report/            # Reporting engine
│   │   └── ...
│   ├── themes/                # UI themes
│   ├── tparty/                # Third-party libraries
│   ├── engine/                # Installation & upgrade
│   ├── public/                # Public-facing pages
│   └── locale/                # Translations
├── tests/                     # Test suite
│   ├── Unit/                  # Unit tests
│   └── Integration/           # Integration tests
├── .github/workflows/         # CI/CD pipelines
├── composer.json              # PHP dependencies
├── phpunit.xml.dist           # PHPUnit configuration
├── phpstan.neon               # PHPStan configuration
├── psalm.xml                  # Psalm configuration
├── .php-cs-fixer.dist.php     # PHP CS Fixer configuration
├── .env.example               # Environment template
└── Dockerfile                 # Container image
```

### Key Design Patterns
- **Active Record** - Database objects extend `dbObject` trait
- **Front Controller** - Single entry point (`index.php`) with routing
- **Dependency Injection** - Global `$dbc` connection object
- **Trait-based Composition** - `dbObject_t` trait for DB operations
- **Parameterized Queries** - All SQL uses prepared statements (security)

## Requirements

- **PHP** 8.1 or higher
- **MySQL** 8.0+ / MariaDB 10.5+
- **Web Server** Apache 2.4+ / Nginx 1.18+
- **Extensions**: pdo_mysql, mbstring, gd, xml, curl, zip, bcmath, intl
- **Composer** 2.x for dependency management

## Installation

### Quick Start (Docker)

```bash
# Clone repository
git clone https://github.com/tim777z/inoERP.git
cd inoERP

# Copy environment template
cp .env.example .env

# Edit .env with your configuration
# At minimum, set DB_PASS to a secure password

# Build and start containers
docker-compose up -d --build

# Access at http://localhost:8080
```

### Manual Installation

```bash
# Clone repository
git clone https://github.com/tim777z/inoERP.git
cd inoERP

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy and configure environment
cp .env.example .env
# Edit .env with your database credentials and settings

# Create database
mysql -u root -p -e "CREATE DATABASE inoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run installation script
php inoerp/engine/install/install_template.php

# Configure web server to point to inoerp/ directory
# Ensure .htaccess is enabled for URL rewriting
```

### Web Server Configuration

**Apache** (`.htaccess` included):
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/inoERP/inoerp
    <Directory /path/to/inoERP/inoerp>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx**:
```nginx
server {
    listen 80;
    root /path/to/inoERP/inoerp;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) { deny all; }
    location ~ \.(inc|sql|log)$ { deny all; }
}
```

## Configuration

All configuration is managed through environment variables. Copy `.env.example` to `.env` and customize:

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_SERVER=localhost
DB_NAME=inoerp
DB_USER=inoerp_user
DB_PASS=your_secure_password_here
DB_PORT=3306
DB_CHARSET=utf8mb4

# Mail
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="inoERP"

# Security (generate with: php -r "echo bin2hex(random_bytes(32));")
APP_KEY=your_32_byte_hex_key
JWT_SECRET=your_jwt_secret
SESSION_LIFETIME=120
```

### Critical Security Notes

1. **Never commit `.env`** - It's in `.gitignore`
2. **Use strong passwords** - Minimum 16 characters, mixed case, numbers, symbols
3. **Generate unique keys** - Use `php -r "echo bin2hex(random_bytes(32));"` for `APP_KEY` and `JWT_SECRET`
4. **Restrict database user** - Create dedicated DB user with minimal privileges
5. **Enable HTTPS** - Always use TLS in production

## Development

### Setting Up Development Environment

```bash
# Install development dependencies
composer install

# Copy environment for development
cp .env.example .env
# Set APP_ENV=development, APP_DEBUG=true

# Run code style fixer
composer cs-fix

# Run static analysis
composer static-analysis
composer psalm

# Run tests
composer test
```

### Adding New Modules

1. Create module directory: `inoerp/modules/your_module/`
2. Create class files following naming convention: `class_your_module.inc`
3. Define `$table_name`, `$primary_column`, `$field_array` properties
4. Use `dbObject_t` trait for database operations
5. Register module in `basics.inc` if needed

### Database Migrations

Schema changes should be made via SQL files in `engine/install/steps/` and applied through the installation/upgrade system.

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test suite
vendor/bin/phpunit --testsuite "Unit Tests"
vendor/bin/phpunit --testsuite "Integration Tests"

# Run single test file
vendor/bin/phpunit tests/Unit/SecurityHelperTest.php
```

### Writing Tests

- Place unit tests in `tests/Unit/`
- Place integration tests in `tests/Integration/`
- Follow PHPUnit 10+ conventions
- Use `SecurityHelper` for input validation in tests
- Mock database connections for unit tests

## Static Analysis

```bash
# PHPStan (level 5)
composer static-analysis

# Psalm
composer psalm

# Both
composer static-analysis && composer psalm
```

### Configuration Files
- `phpstan.neon` - PHPStan rules and exclusions
- `psalm.xml` - Psalm configuration with suppressed issues for legacy code

## Code Style

This project follows **PSR-12** coding standard with additional rules.

```bash
# Check code style
composer lint

# Auto-fix code style
composer lint:fix

# Or directly with PHP CS Fixer
composer cs-fix
```

### Key Style Rules
- PSR-12 base standard
- Short array syntax `[]`
- Strict types `declare(strict_types=1);`
- Type hints on all function parameters and returns
- PHPDoc for all public methods
- Single quotes for strings (unless interpolation needed)
- Trailing commas in multiline arrays

## Security

### Security Features Implemented
- ✅ **Parameterized Queries** - All database queries use PDO prepared statements
- ✅ **Input Validation** - `SecurityHelper` class for sanitization
- ✅ **Environment-based Config** - No hardcoded secrets
- ✅ **XSS Prevention** - `SecurityHelper::sanitizeHtml()` for output
- ✅ **CSRF Protection** - Token-based form protection
- ✅ **SQL Injection Prevention** - `SecureSearchBuilder` for dynamic queries
- ✅ **Timing Attack Prevention** - `SecurityHelper::timingSafeEquals()`
- ✅ **Secure Password Handling** - PHP `password_hash()` / `password_verify()`

### Reporting Security Issues

Please report security vulnerabilities privately to security@inoerp.com. Do not open public issues for security problems.

### Security Checklist for Contributors
- [ ] No hardcoded credentials or secrets
- [ ] All SQL uses parameterized queries
- [ ] User input validated with `SecurityHelper`
- [ ] Output escaped with `SecurityHelper::sanitizeHtml()`
- [ ] No direct `$_GET`/`$_POST`/`$_REQUEST` in SQL
- [ ] New dependencies scanned with `composer audit`

## Docker

### Development
```bash
docker-compose up -d
# Access at http://localhost:8080
```

### Production
```bash
# Build production image
docker build -t inoerp:latest --target production .

# Run with production config
docker run -d \
  --name inoerp \
  -p 80:80 \
  --env-file .env \
  inoerp:latest
```

### Multi-stage Build
The `Dockerfile` uses multi-stage builds for smaller production images:
- **Base** - PHP 8.2 + extensions
- **Dependencies** - Composer install
- **Production** - Optimized, no dev dependencies

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes following the code style
4. Run tests and static analysis: `composer test && composer static-analysis`
5. Commit with conventional commits: `git commit -m "feat: add amazing feature"`
6. Push to your fork: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Commit Message Convention
```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `security`

### Pull Request Requirements
- [ ] All tests pass
- [ ] Static analysis passes (PHPStan level 5, Psalm)
- [ ] Code style conforms to PSR-12
- [ ] No hardcoded secrets
- [ ] Documentation updated if needed
- [ ] CHANGELOG.md updated

## License

This project is licensed under the GNU General Public License v3.0 - see [LICENSE.txt](LICENSE.txt) for details.

## Support

- **Documentation**: [Wiki](https://github.com/tim777z/inoERP/wiki)
- **Issues**: [GitHub Issues](https://github.com/tim777z/inoERP/issues)
- **Security**: security@inoerp.com
- **Community**: [Discussions](https://github.com/tim777z/inoERP/discussions)

---

**Built with ❤️ by the inoERP Team**