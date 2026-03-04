# Artwork – Claude Code Richtlinien

## Projektübersicht

Artwork ist ein Projektorganisations-Tool für die Planung von Projekten mit Events, Aufgaben und Verantwortlichkeiten. Teams können damit wesentliche Projektkomponenten verwalten.

## Tech-Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue.js 3 mit Inertia.js
- **CSS**: Tailwind CSS 3.4
- **Datenbank**: MySQL/MariaDB
- **Suche**: Meilisearch
- **Echtzeit**: Soketi (WebSockets), Pusher, Laravel Echo
- **Queue**: Laravel Horizon

## Entwicklungsumgebung (DDEV)

Alle Befehle **immer** über DDEV ausführen:

```bash
ddev start                          # Umgebung starten
ddev npm run dev                    # Frontend-Dev-Server
ddev artisan migrate                # Migrationen ausführen
ddev artisan migrate:fresh --seed   # Frische DB mit Seed-Daten
ddev composer install               # PHP-Abhängigkeiten
ddev npm install                    # JS-Abhängigkeiten
ddev artisan queue:work             # Queue-Worker
```

## Architektur

### Modularer Monolith

Das Projekt folgt einer modularen Monolith-Architektur:

```
artwork/
├── Core/                           # Geteilte Infrastruktur (BaseRepository, Services, Models)
└── Modules/                        # Feature-Module
    ├── Category/
    │   ├── Models/
    │   ├── Services/
    │   ├── Repositories/
    │   ├── DTO/
    │   ├── Enums/
    │   └── ...
    ├── Event/
    ├── Project/
    ├── Shift/
    └── ... (60+ Module)

app/
├── Http/Controllers/               # Alle Controller zentral (NICHT pro Modul)
├── Policies/
└── ...
```

### Namespaces (PSR-4)

- `Artwork\Core\{Kategorie}\{Klasse}` – z.B. `Artwork\Core\Database\Repository\BaseRepository`
- `Artwork\Modules\{Modul}\{Ordner}\{Klasse}` – z.B. `Artwork\Modules\Event\Services\EventService`
- `App\Http\Controllers\{Controller}` – Controller liegen zentral in `/app`

### Datenfluss

```
Controller → Service → Repository → Model → Datenbank
Rückweg: Model → Repository → Service → DTO/Resource → Controller → Frontend
```

### Patterns

**Services** (`Artwork\Modules\{Modul}\Services\`):
- Enthalten die gesamte Business-Logik
- Werden per Constructor Injection als `readonly` Properties in Controller injiziert
- Einfache Module: ein Service (z.B. `CategoryService`)
- Komplexe Module: mehrere spezialisierte Services (z.B. `EventService`, `EventCollisionService`, `EventVerificationService`)

**Repositories** (`Artwork\Modules\{Modul}\Repositories\`):
- Erweitern `Artwork\Core\Database\Repository\BaseRepository`
- Kapseln Datenbankzugriffe
- Services nutzen Repositories, nicht direkt Models

**DTOs** (`Artwork\Modules\{Modul}\DTO\`):
- Gebaut mit Spatie LaravelData (`spatie/laravel-data`)
- Transformieren Models in frontend-freundliche Formate

**Models** (`Artwork\Modules\{Modul}\Models\`):
- Erweitern `Artwork\Core\Database\Models\Model`
- Nutzen Traits: `SoftDeletes`, `HasFactory`, `Prunable`

**Enums** (`Artwork\Modules\{Modul}\Enums\`):
- Native PHP 8.1+ Enums (Backed Enums)

**Resources** (`Artwork\Modules\{Modul}\Http\Resources\`):
- Laravel API Resources für Response-Transformation

### Controller-Stil

Controller dünn halten. Business-Logik gehört in Services:

```php
class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly ShiftService $shiftService,
        private readonly NotificationService $notificationService,
    ) {}
}
```

## Code Quality

```bash
ddev composer phpstan    # Statische Analyse
ddev composer phpcs      # Code-Style prüfen
ddev composer phpcbf     # Code-Style automatisch fixen
```

## Internationalisierung (i18n)

### Übersetzungsdateien

- `lang/de.json` und `lang/en.json`
- Schlüssel ist **immer der englische Text** (flache Struktur)
- **Immer beide Dateien gleichzeitig aktualisieren**

```json
// lang/en.json
{ "Save changes": "Save changes" }

// lang/de.json
{ "Save changes": "Änderungen speichern" }
```

### Frontend-Nutzung

```vue
<span>{{ $t('Save changes') }}</span>
```

## Berechtigungen (Permissions)

### Naming-Konvention

Format: `"can <aktion> <ressource>"`

Beispiele: `"can view contracts"`, `"can edit contracts"`, `"can create contracts"`, `"can delete contracts"`

### Neue Berechtigungen

Müssen in `RolesAndPermissionsSeeder.php` eingetragen werden.

### Frontend-Check

```javascript
import { usePermission } from "@/Composeables/Permission.js";
import { usePage } from "@inertiajs/vue3";

const { can, hasAdminRole } = usePermission(usePage().props);

// IMMER hasAdminRole() einschließen – Admins dürfen alles!
if (can('can edit document requests') || hasAdminRole()) {
  // Aktion erlaubt
}
```

## Neue Projekt-Komponenten erstellen

### Checkliste

1. **Enum-Wert hinzufügen**: `artwork/Modules/Project/Enum/ProjectTabComponentEnum.php`
2. **Vue-Komponente erstellen**: `resources/js/Pages/Projects/Components/YourComponent.vue`
3. **In TabContent.vue registrieren**: Import + Component-Mapping
4. **Command aktualisieren**: `artwork:add-new-components`
5. **Übersetzungen**: Lesbarer Text in `de.json` und `en.json` (NICHT der Enum-Name!)
6. **Icon**: `PropertyIcon.vue` mit tabler.io Icon
7. **Print-Layout-Support**:
   - Backend: Neuen `case` in `ProjectPrintLayoutController::show()`
   - PrintLayout-Komponente: `PrintLayoutBuilderYourComponent.vue` in `resources/js/Pages/Projects/BuilderComponents/`
   - Registrierung in `ProjectPrintLayoutWindow.vue` im `componentMapping`

### component.id vs component.component_id

In Builder-Komponenten (`BuilderTextArea`, `BuilderTextField`, `BuilderCheckbox`, `BuilderDropDown`, `BuilderLinkComponent`, `BuilderLinkListComponent`) **immer `component.component_id`** verwenden – NICHT `component.id`:

```js
// ✅ Richtig:
project['TextArea']?.[component.component_id]?.data?.text

// ❌ Falsch:
project['TextArea']?.[component.id]?.data?.text
```

Gilt für alle Builder- **und** PrintLayoutBuilder-Dateien.

## UI-Komponenten

### Icons (tabler.io)
```vue
<PropertyIcon icon="IconName" />
```

### Modal (ArtworkBaseModal)
```javascript
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
```
```vue
<ArtworkBaseModal
    :title="$t('Modal Title')"
    :description="$t('Modal description text.')"
    @close="closeModal"
>
  <!-- Inhalt im Default-Slot -->
</ArtworkBaseModal>
```
`:title` und `:description` als Props verwenden (NICHT als Slots). Event ist `close` (NICHT `closed`).

### Input (BaseInput)
```javascript
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
```
```vue
<BaseInput v-model="value" :label="$t('Label')" id="field_id" />
<BaseInput type="time" id="start_time" v-model="startTime" :label="$t('Start time')" />
<BaseInput type="number" id="break_minutes" v-model.number="breakMinutes" :label="$t('Break (minutes)')" :min="0" :step="1" />
```

### Tooltip
```vue
<ToolTipComponent>
  <template #content>{{ $t('Tooltip text') }}</template>
</ToolTipComponent>
```

### User Tooltip
```vue
<NewUserToolTip :user="user" :id="uniqueId" height="10" width="10" />
```

## Deployment

- `dev` → `staging` → `main`
- **Production**: `main` (stabilster Branch)
- **Staging**: `staging` (Pre-Release-Tests)
- **Development**: `dev` (Feature-Integration)

## Artisan-Befehle für neue Ressourcen

```bash
ddev artisan make:model ModelName -mf          # Model + Migration + Factory
ddev artisan make:controller ControllerName --resource
ddev artisan make:migration create_table_name
ddev artisan artwork:update-permissions
ddev artisan artwork:add-new-components
```

## Tests

Tests sind aktuell veraltet und nicht gepflegt. Tests **nur auf explizite Anfrage** ausführen.
