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

---

## Internationalization (i18n)

### Translation Files
- **Location**: `lang/de.json` and `lang/en.json`
- **Key Format**: Always use the **English text** as the key (flat structure)
- **Rule**: Always update **both files** simultaneously

```json
// lang/en.json
{
  "Save changes": "Save changes"
}

// lang/de.json
{
  "Save changes": "Änderungen speichern"
}
```

### Frontend Usage
```vue
<template>
  <span>{{ $t('Save changes') }}</span>
</template>
```

---

## Permissions

### Seeder
All new permissions must be added to `RolesAndPermissionsSeeder.php`

### Naming Convention
Format: `"can <action> <resource>"`

Examples:
- `"can view contracts"`
- `"can edit contracts"`
- `"can create contracts"`
- `"can delete contracts"`

### Frontend Permission Check
```javascript
import { usePermission } from "@/Composeables/Permission.js";
import { usePage } from "@inertiajs/vue3";

const { can, hasAdminRole } = usePermission(usePage().props);

// ALWAYS include hasAdminRole() - admins can do everything!
if (can('can edit document requests') || hasAdminRole()) {
  // Action allowed
}
```

---

## Creating New Project Components

### Checklist
1. **Add Enum Value**: `artwork/Modules/Project/Enum/ProjectTabComponentEnum.php`
2. **Create Vue Component**: `resources/js/Pages/Projects/Components/YourComponent.vue`
3. **Register in TabContent.vue**: Import and add to component mapping
4. **Add to Command**: Update `artwork:add-new-components` command
5. **Add Translations**: Human-readable display text in `de.json` and `en.json` (NOT the enum name!)
6. **Add Icon**: Use `PropertyIcon.vue` with tabler.io icon
7. **Print Layout Support**: Damit die Komponente auch im Drucklayout (PDF) angezeigt wird, müssen drei Stellen angepasst werden. **Wichtig:** Die Builder-Komponenten für die Projektübersicht (`BuilderYourComponent.vue`) und die PrintLayout-Komponenten für das Drucklayout (`PrintLayoutBuilderYourComponent.vue`) sind vollständig getrennt, damit Änderungen an der Projektübersicht das Drucklayout nicht beeinflussen.
   - **Backend**: Neuen `case` im `switch`-Block in `ProjectPrintLayoutController::show()` (`app/Http/Controllers/ProjectPrintLayoutController.php`) hinzufügen, der die benötigten Daten aus dem Projekt lädt.
   - **PrintLayout-Komponente erstellen**: Eine Vue-Komponente `PrintLayoutBuilderYourComponent.vue` in `resources/js/Pages/Projects/BuilderComponents/` anlegen, die die Daten im Druckformat rendert. Diese Datei ist unabhängig von der gleichnamigen `BuilderYourComponent.vue` (Projektübersicht) und kann ein eigenes Layout/Styling haben.
   - **componentMapping registrieren**: Die **PrintLayout**-Komponente in `ProjectPrintLayoutWindow.vue` importieren und im `componentMapping`-Objekt registrieren. Der Key muss `"Builder" + EnumValue` sein (z.B. `BuilderArtistNameDisplayComponent` für den Enum-Wert `ArtistNameDisplayComponent`), aber der Import zeigt auf die `PrintLayoutBuilder...`-Datei.

### ⚠️ Wichtig: component.id vs component.component_id in Builder-Komponenten

In nicht-speziellen (custom) Builder-Komponenten wie `BuilderTextArea`, `BuilderTextField`, `BuilderCheckbox`, `BuilderDropDown`, `BuilderLinkComponent`, `BuilderLinkListComponent` muss im Frontend **immer `component.component_id`** verwendet werden, um auf Projektdaten zuzugreifen – **NICHT `component.id`**.

**Hintergrund:** Die `components`-Prop enthält `ProjectManagementBuilder`-Einträge. `component.id` ist die Builder-ID, aber das Backend (`ProjectController::mapProjectsToComponents`) speichert die Werte unter `Component.id` (= `component_id` des Builders). Daher:

```js
// ✅ Richtig:
project['TextArea']?.[component.component_id]?.data?.text

// ❌ Falsch:
project['TextArea']?.[component.id]?.data?.text
```

Dies gilt für alle Builder- **und** PrintLayoutBuilder-Dateien gleichermaßen.

---

## UI Component Snippets

### Icons (tabler.io)
```vue
<PropertyIcon icon="IconName" />
```
Reference: https://tabler.io/icons

### Modal (ArtworkBaseModal)
**Import:**
```javascript
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
```
**Usage:** Use `:title` and `:description` props (NOT slots). Emits `close` event (NOT `closed`).
```vue
<ArtworkBaseModal
    :title="$t('Modal Title')"
    :description="$t('Modal description text.')"
    @close="closeModal"
>
  <!-- Modal content in default slot -->
</ArtworkBaseModal>
```

### Input (BaseInput)
**Import:**
```javascript
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
```
**Basic usage:**
```vue
<BaseInput v-model="value" :label="$t('Label')" id="field_id" />
```
**Time input:**
```vue
<BaseInput type="time" id="start_time" v-model="startTime" :label="$t('Start time')" />
```
**Number input (e.g. for minutes):**
```vue
<BaseInput type="number" id="break_minutes" v-model.number="breakMinutes" :label="$t('Break (minutes)')" :min="0" :step="1" />
```

### Tooltip
```vue
<ToolTipComponent>
  <template #content>
    {{ $t('Tooltip text') }}
  </template>
</ToolTipComponent>
```

### User Tooltip
```vue
<NewUserToolTip :user="user" :id="uniqueId" height="10" width="10" />
```

---

## Testing Policy

**IMPORTANT**: Do NOT run tests automatically. The test suite is currently outdated and not maintained. Only run tests when explicitly requested by the user.
