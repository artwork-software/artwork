# Artwork Developer Guidelines

## Project Overview
Artwork is a project organization tool for scheduling projects with events, tasks, and responsibilities. It helps teams track essential project components using Laravel 12 (PHP 8.2+) with Vue.js 3 frontend.

## Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue.js 3 with Inertia.js
- **CSS Framework**: Tailwind CSS 3.4
- **Database**: MySQL/MariaDB
- **Search**: Meilisearch
- **Real-time**: Soketi (WebSockets), Pusher, Laravel Echo
- **Queue**: Laravel Horizon
- **Testing**: PHPUnit (NOT Pest)

## Development Setup (DDEV Required)

### Initial Setup
1. **Prerequisites**: Ensure [DDEV](https://ddev.readthedocs.io) is installed
2. **Project Initialization** (one-time only):
   ```bash
   ddev config
   ddev start
   ddev composer install
   ddev npm install
   ddev artisan key:generate
   ddev artisan migrate:fresh --seed
   ddev artisan storage:link
   ```

### Daily Development Workflow
```bash
# Start the environment
ddev start

# Frontend development
ddev npm run dev

# Queue worker (separate terminal)
ddev artisan queue:work

# Database operations
ddev artisan migrate        # Run pending migrations
ddev artisan migrate:fresh --seed  # Fresh database with seed data
```

## Testing Configuration

### Test Structure
- **Base Test Class**: `tests/TestCase.php` - extends Laravel's TestCase with:
  - `DatabaseTransactions` trait (NOT RefreshDatabase)
  - Cache clearing for EventType and ProjectTab
  - Locale setup (English)
  - Vite disabled for tests
  - `CreateAdminUser` trait for user creation

- **Test Suites**:
  - `Unit`: Located in `tests/Unit/` - Unit tests for individual components
  - `Feature`: Located in `tests/Feature/` - Integration tests

### PHPUnit Configuration (`phpunit.xml`)
- Bootstrap: `vendor/autoload.php`
- Test environment variables automatically set (APP_ENV=testing, etc.)
- Source coverage includes both `./app` and `./artwork` directories
- Colors enabled for better output

### Running Tests
```bash
# All tests
ddev artisan test
# or
ddev pest  # Alternative command

# Specific test suite
ddev artisan test --testsuite=Unit
ddev artisan test --testsuite=Feature

# Specific test file
vendor/bin/phpunit tests/Unit/YourTestFile.php

# With filter for specific test method
ddev artisan test --filter=testMethodName
```

### Writing Tests
**Important**: 
- Use PHPUnit, NOT Pest tests
- NEVER use `RefreshDatabase` trait
- All base tests use `DatabaseTransactions` trait for cleanup
- Tests require DDEV environment to be running for database access

**Example Unit Test Structure**:
```php
<?php

namespace Tests\Unit\YourModule;

use Tests\TestCase;

class YourClassTest extends TestCase
{
    public function testBasicFunctionality(): void
    {
        // Your test logic here
        $this->assertTrue(true);
    }
}
```

**Testing Best Practices**:
- Use factories for model creation: `YourModel::factory()->create()`
- Mock external services: `$this->createMock(ServiceClass::class)`
- Test HTTP endpoints with proper assertions: `$response->assertStatus(200)`
- Use descriptive test method names starting with `test`

## Code Quality Tools
```bash
# Static analysis
ddev composer phpstan

# Code style checking
ddev composer phpcs

# Automatic code style fixes
ddev composer phpcbf
```

## Project Structure Specifics
- `/app` - Core Laravel application code
- `/artwork` - **Custom modules** (important: this is included in test coverage)
- `/resources/js` - Vue.js components and frontend assets
- `/tests` - PHPUnit test files organized by Unit/Feature
- Custom artisan commands:
  - `ddev artisan artwork:update-permissions`
  - `ddev artisan artwork:add-new-components`

## Development Best Practices

### Laravel-Specific
1. Follow Laravel naming conventions strictly
2. Keep controllers thin - use Services for business logic
3. Use Laravel's built-in validation
4. Utilize proper type hints and DocBlocks
5. The project uses custom modules in `/artwork` directory

### Frontend (Vue.js + Inertia)
1. Follow Vue.js component structure in `resources/js`
2. Use Tailwind utility classes for styling
3. Components are organized with clear separation of concerns

### Testing Guidelines
1. Write tests for all new features
2. Unit tests should test individual components in isolation
3. Feature tests should test complete user workflows
4. Mock external dependencies appropriately
5. Use database transactions for test isolation

## Environment Notes
- **Database**: Tests require active DDEV environment with database connection
- **Caching**: EventType and ProjectTab caches are cleared in test setup
- **Locale**: Tests run in English locale by default
- **Queue**: Use `sync` connection in testing environment
- **Mail**: Uses array driver in tests (no actual emails sent)

## Deployment
- **Branch Structure**: `dev` → `staging` → `main`
- **Production**: `main` branch (most stable)
- **Staging**: `staging` branch (pre-release testing)
- **Development**: `dev` branch (feature integration)

## Common Artisan Commands
```bash
# Model with migration and factory
ddev artisan make:model ModelName -mf

# Controller with resource methods
ddev artisan make:controller ControllerName --resource

# Create migration
ddev artisan make:migration create_table_name

# Create test (use PHPUnit structure)
ddev artisan make:test TestName --unit  # for unit tests
ddev artisan make:test TestName         # for feature tests
```

This project requires DDEV for proper development environment setup and testing. All tests depend on database connectivity through DDEV's containerized environment.
