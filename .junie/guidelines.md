# Artwork Developer Guidelines

## Project Overview
Artwork is a project organization tool for scheduling projects with events, tasks, and responsibilities. It helps teams track essential project components.

## Tech Stack
- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue.js 3 with Inertia.js
- **CSS Framework**: Tailwind CSS 3.4
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

## Development Setup (DDEV)
1. **Installation**:
   - Stelle sicher, dass [DDEV](https://ddev.readthedocs.io) installiert ist.
   - Projekt initialisieren (einmalig): `ddev config`
   - Projekt starten: `ddev start`
   - Abhängigkeiten installieren: `ddev composer install` und `ddev npm install`
   - App Key generieren: `ddev artisan key:generate`
   - Datenbank migrieren: `ddev artisan migrate:fresh --seed`

2. **Running the Application**:
   - Backend starten: `ddev start`
   - Frontend entwickeln: `ddev npm run dev`
   - Queue-Worker starten: `ddev artisan queue:work`
   - Storage verlinken: `ddev artisan storage:link`

## Running Tests
- Alle Tests ausführen: `ddev artisan test` oder `ddev pest`
- Spezifisches Testsuite ausführen: `ddev artisan test --testsuite=Unit`

## Code Quality Tools
- Statische Analyse: `ddev composer phpstan`
- Code Style Prüfung: `ddev composer phpcs`
- Automatische Code Style Korrektur: `ddev composer phpcbf`

## Common Commands
- Migration erstellen: `ddev artisan make:migration create_table_name`
- Model erstellen: `ddev artisan make:model ModelName`
- Controller erstellen: `ddev artisan make:controller ControllerName`
- Test erstellen: `ddev artisan make:test TestName`

## Maintenance
- Berechtigungen aktualisieren: `ddev artisan artwork:update-permissions`
- Neue Komponenten hinzufügen: `ddev artisan artwork:add-new-components`

## Deployment
- Branch-Struktur: `dev` → `staging` → `main`
- Produktions-Branch: `main` (stabilste Version)
- Staging-Branch: `staging` (Pre-Release Testing)
- Entwicklungs-Branch: `dev` (Feature-Integration)

## Tests
- Verwende beim Schreiben von Unittests PHPUnit, schreibe *KEINE* Pest Tests.
- Verwende *NIEMALS* den RefreshDatabase Trait in Tests.
- Alle BasisTests sind schon so konfiguriert, dass sie den "DatabaseTransactions" Trait verwenden, um die Datenbank nach jedem Test zu bereinigen.


## Best Practices
1. Halte dich an Laravel-Konventionen für Struktur und Benennung
2. Schreibe Tests für neue Features
3. Nutze Type-Hints und DocBlocks
4. Halte Controller schlank, nutze Services für Business-Logik
5. Verwende Laravel's integrierte Validierung
6. Folge der Vue.js-Komponentenstruktur in `resources/js`
7. Nutze Tailwind Utility-Klassen für das Styling
