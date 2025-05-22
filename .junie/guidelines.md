# Artwork Developer Guidelines

## Project Overview
Artwork is a project organization tool for scheduling projects with events, tasks, and responsibilities. It helps teams track essential project components.

## Tech Stack
- **Backend**: Laravel 10 (PHP 8.2+)
- **Frontend**: Vue.js 3 with Inertia.js
- **CSS Framework**: Tailwind CSS
- **Database**: MySQL/MariaDB
- **Search**: Meilisearch
- **Real-time**: Soketi (WebSockets), Pusher, Laravel Echo
- **Queue**: Laravel Horizon
- **Testing**: Pest/PHPUnit

## Project Structure
- `/app` - Core application code
- `/artwork` - Custom modules
- `/config` - Configuration files
- `/database` - Migrations and seeders
- `/resources` - Frontend assets and Vue components
- `/routes` - API and web routes
- `/tests` - Test files (Unit and Feature)

## Development Setup
1. **Installation**:
   - Use Laravel Sail (Docker): `./vendor/bin/sail up`
   - Install dependencies: `sail composer install` and `sail npm install`
   - Generate app key: `sail artisan key:generate`
   - Migrate database: `sail artisan migrate:fresh --seed`

2. **Running the Application**:
   - Start backend: `sail up`
   - Start frontend: `sail npm run dev`
   - Start queue worker: `sail artisan queue:work`
   - Link storage: `sail artisan storage:link`

## Running Tests
- Run all tests: `sail artisan test` or `sail pest`
- Run specific test suite: `sail artisan test --testsuite=Unit`

## Code Quality Tools
- Static analysis: `composer phpstan`
- Code style checking: `composer phpcs`
- Automatic code style fixing: `composer phpcbf`

## Common Commands
- Create migration: `sail artisan make:migration create_table_name`
- Create model: `sail artisan make:model ModelName`
- Create controller: `sail artisan make:controller ControllerName`
- Create test: `sail artisan make:test TestName`

## Maintenance
- Update permissions: `sail artisan artwork:update-permissions`
- Add new components: `sail artisan artwork:add-new-components`

## Deployment
- Branch structure: `dev` → `staging` → `main`
- Production branch: `main` (most stable version)
- Staging branch: `staging` (pre-release testing)
- Development branch: `dev` (feature integration)

## Best Practices
1. Follow Laravel conventions for naming and structure
2. Write tests for new features
3. Use type hints and docblocks
4. Keep controllers thin, move business logic to services
5. Use Laravel's built-in validation
6. Follow Vue.js component structure in resources/js
7. Use Tailwind utility classes for styling
