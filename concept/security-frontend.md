# Arbeitspaket: Frontend-Foundation für externen Zugriff

## Kontext

Aufbauend auf der Auth-Foundation (Paket "Auth-Foundation für externen Zugriff") wird in diesem Paket das gesamte Vue-Frontend für die externe Welt aufgebaut: eigener Vite-Entry-Point, eigenes Blade-Template, eigenes Layout, eigene reduzierte Mainnav, Login-/Magic-Link-UX, Dashboard-Skelett und Inertia-Share-Logik für externe Routen.

**Ziel:** Vollständige UI-Trennung zwischen interner und externer Welt. Kein internes Vue-Bundle landet im Browser einer externen Person, keine interne Permission/Rollen-Information ist im externen JS-State erreichbar.

**Voraussetzung:** Auth-Foundation-Paket ist gemerged. Backend-Routen `external.login.form`, `external.login.request`, `external.login.redeem`, `external.logout`, `external.dashboard` existieren als Skelette.

**Nicht Teil dieses Pakets:**
- Einladungs-Flow (Service + UI im CRM-Index/Projekttab)
- CRM-Self-Erfassungs-Maske (Edit-Logik mit Staging/Approval)
- Tab-Anzeige für externe User (kommt mit Scope-Middleware)
- Notifications-Trigger
- Admin-UI für globale Settings

In diesem Paket entstehen Skelett-Seiten für Dashboard und CRM-Self-View (read-only, ohne Edit), damit die Mainnav etwas zum Verlinken hat und die Trust-Boundary testbar wird. Die Inhalte dieser Seiten sind explizit minimal.

## Vorarbeit

- Lies `concept/security-concept.md`, insbesondere Abschnitt 6.4 (Frontend-Isolation).
- Lies `resources/js/app.js` und `resources/js/Layouts/AppLayout.vue` als Vergleichsbasis — daraus entstehen die externen Pendants. Achte besonders auf, was im internen Layout passiert, das im externen NICHT passieren darf (Echo-Listener, `reloadRolesAndPermissions()`, `LaravelPermissionToVueJS`).
- Lies `resources/views/app.blade.php` als Vorlage für das externe Blade-Template.
- Lies `resources/js/Pages/Auth/Login.vue` als Style-/Layout-Referenz für die externe Login-Seite.
- Branch von `dev`: `feat/external-access-frontend-foundation`.

## Architektur — was getrennt wird

| Aspekt | Intern | Extern |
|---|---|---|
| Vite-Entry | `resources/js/app.js` | `resources/js/app-external.js` |
| Build-Input | unverändert | neu in `vite.config.js`, eigener Manifest-Bereich |
| Blade-Root | `resources/views/app.blade.php` | `resources/views/app-external.blade.php` |
| Inertia-Page-Glob | `./Pages/**/*.vue` | `./Pages/External/**/*.vue` |
| Inertia-Middleware | `HandleInertiaRequests` | `HandleExternalInertiaRequests` (aus Auth-Paket, hier erweitert) |
| App-Layout | `AppLayout.vue` | `External/Layouts/ExternalAppLayout.vue` |
| Mainnav | `SubMenu.vue` | `External/Layouts/ExternalSubMenu.vue` |
| Permission-Composable | `Permission.js` | nicht genutzt; externe Pages dürfen kein Permission-State |
| Echo/Broadcasting | aktiv | nicht initialisiert |
| Plugin: `LaravelPermissionToVueJS` | registriert | NICHT registriert |
| Plugin: PrimeVue, i18n | registriert | registriert (gleiche Plugins, nicht UI brechen) |

Diese Trennung ist nicht nur Coding-Stil, sondern Trust-Boundary: das externe Bundle darf nicht mal die Möglichkeit haben, intern-spezifische Globals (`window.Laravel.jsPermissions`) zu setzen oder Echo-Channels für `notifications.{userId}` zu abonnieren.

---

## Schritt 1: Vite-Build-Konfiguration

### `vite.config.js` erweitern

Zweiter Input neben `resources/js/app.js`:

```js
laravel({
    input: [
        'resources/js/app.js',
        'resources/js/app-external.js',
    ],
    refresh: true,
}),
```

Der `laravel-vite-plugin` erzeugt damit zwei separate Bundles mit je eigenem Manifest-Eintrag. Beim Build (`npm run build`) entstehen `public/build/assets/app-*.js` und `public/build/assets/app-external-*.js` mit jeweils eigenen Code-Splits.

**Wichtig zu verifizieren nach dem Build:**
- `public/build/manifest.json` enthält beide Entries
- Beide Bundles teilen sich keine sensiblen Module — ein Smoke-Check, der das externe Bundle nach Strings wie `permissionsArray`, `rolesArray`, `LaravelPermissionToVueJS`, `reloadRolesAndPermissions` durchsucht. Findet er Treffer, ist die Trennung kaputt. Idealerweise als CI-Check formalisierbar (siehe Acceptance Criteria).

### `resources/views/app-external.blade.php`

Neue Blade-Datei analog zur bestehenden `app.blade.php`, mit drei wesentlichen Unterschieden:

```blade
<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Artwork') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            html { scroll-behavior: smooth; }
        </style>

        @routes
        @vite(['resources/js/app-external.js'])
        @inertiaHead

        {{-- KEIN window.Laravel.jsPermissions, KEINE auth()->user()-Auflösung --}}
    </head>
    <body class="font-sans antialiased artwork">
        @inertia
    </body>
</html>
```

Bewusst weggelassen vs. `app.blade.php`:
- `window.Laravel.jsPermissions` (das ist die größte Leak-Quelle im aktuellen Setup)
- `auth()->user()->jsPermissions()` — der Aufruf würde auf `ExternalAccess` ohnehin fehlschlagen, aber wir lassen ihn vorsorglich weg

### Inertia-Root anpassen

Inertia muss wissen, welches Blade-Template für externe Routen zu verwenden ist. Lösung: `HandleExternalInertiaRequests::rootView()` überschreiben:

```php
class HandleExternalInertiaRequests extends Middleware
{
    public function rootView(Request $request): string
    {
        return 'app-external';
    }
    // ...
}
```

Diese Methode existiert in der Inertia-Basis-Middleware und kann pro Subclass überschrieben werden. Damit wird automatisch das externe Blade gerendert, sobald die Route in der `external`-Middleware-Group liegt.

---

## Schritt 2: Vue-Entry-Point `app-external.js`

Neue Datei `resources/js/app-external.js`. Basis-Aufbau analog zu `app.js`, aber:

**Bewusst NICHT importieren / nutzen:**
- `LaravelPermissionToVueJS` und `reloadRolesAndPermissions` — kein Permission-State
- `window.Echo` / Laravel Echo Setup — keine Broadcast-Verbindungen
- `bootstrap.js` ggf. abklären: falls dieses File Echo initialisiert, eine eigene `bootstrap-external.js` ohne Echo schreiben (siehe unten)

**Beibehalten / nötig:**
- Vue + Inertia (Plugin + `createInertiaApp`)
- vue-i18n (gleiches Pattern, gleiche Lang-JSON-Dateien)
- PrimeVue + Tooltip (für konsistente UI-Components)
- Tailwind CSS (`app.css`, `global.css`) — bewusst dieselben CSS-Bundles, damit Branding einheitlich bleibt

### `resources/js/bootstrap-external.js`

Das interne `bootstrap.js` initialisiert wahrscheinlich Axios + Echo + Pusher. Für extern brauchen wir nur Axios mit CSRF-Header. Echo bleibt aus.

```js
import axios from 'axios'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const token = document.querySelector('meta[name="csrf-token"]')
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}
```

Kein `window.Echo`, kein `Pusher`-Setup. Damit ist klar: externe Pages können nicht versehentlich einen Broadcast-Channel öffnen.

### Inertia-Page-Resolver

```js
const pages = import.meta.glob('./Pages/External/**/*.vue')

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => {
        const page = pages[`./Pages/External/${name}.vue`]
        if (!page) throw new Error(`External page not found: ${name}`)
        return page()
    },
    // ...
})
```

**Wichtig:** Der Glob-Pfad wird auf `./Pages/External/**/*.vue` beschränkt. Inertia kann damit ausschließlich Pages aus dem externen Verzeichnis auflösen. Wenn ein Backend-Controller versehentlich `Inertia::render('Projects/Show', ...)` im externen Kontext aufruft, wirft der Resolver einen Fehler — gewünschter Fail-Fast.

### Session-Expiry-Handler

Den `router.on('invalid')`-Handler aus `app.js` übernehmen, aber die Meldung anpassen:

```js
router.on('invalid', (event) => {
    event.preventDefault()
    const status = event.detail.response?.status
    if (status === 401 || status === 419 || status === 409) {
        // Externe Session abgelaufen → kein Reload, sondern Redirect auf Login-Form
        window.location.href = '/external/login?expired=1'
    }
})
```

Unterschied zum internen `alert + reload`: bei Externen führt Reload zu einer 401-Schleife (sie haben keine Möglichkeit mehr, sich einzuloggen ohne neue Email). Direkter Redirect ist die saubere UX.

---

## Schritt 3: `HandleExternalInertiaRequests` ausbauen

Die Skelett-Version aus dem Auth-Foundation-Paket wird hier zur finalen Form. Datei: `artwork/Modules/ExternalAccess/Http/Middleware/HandleExternalInertiaRequests.php`.

```php
class HandleExternalInertiaRequests extends \Inertia\Middleware
{
    public function rootView(Request $request): string
    {
        return 'app-external';
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        /** @var ExternalAccess|null $external */
        $external = $request->user('external');

        return array_merge(parent::share($request), [
            'auth' => [
                'external' => $external ? $this->shareExternal($external) : null,
            ],
            'accessible_scopes' => fn () => $external
                ? $this->resolveAccessibleScopes($external)
                : [],
            'crm_access_expires_at' => $external?->crm_access_expires_at?->toIso8601String(),
            'page_title' => $this->generalSettings->page_title ?: config('app.name'),
            'small_logo' => $this->resolveSmallLogo(),
            'big_logo' => $this->resolveBigLogo(),
            'banner' => $this->resolveBanner(),
            'company_name' => $this->generalSettings->business_name ?? null,
            'translations' => fn () => $this->loadTranslations($request->getLocale()),
            'flash' => fn () => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => $request->getLocale(),
            'impressumLink' => $this->generalSettings->impressum_link ?? null,
            'privacyLink' => $this->generalSettings->privacy_link ?? null,
        ]);
    }

    private function shareExternal(ExternalAccess $external): array
    {
        return [
            'id' => $external->id,
            'crm_contact_id' => $external->crm_contact_id,
            'display_name' => $external->crmContact->display_name,
            'email' => $external->email,
            'crm_access_expires_at' => $external->crm_access_expires_at?->toIso8601String(),
        ];
    }

    /**
     * Liefert die aktuell gültigen Scopes als minimale Liste für die Mainnav.
     * Pro Scope: id, project name (display only), tab name, access_type, valid_to.
     */
    private function resolveAccessibleScopes(ExternalAccess $external): array
    {
        return $external->scopes()
            ->currentlyValid()
            ->with(['project:id,name', 'projectTab:id,name'])
            ->get()
            ->map(fn ($scope) => [
                'id' => $scope->id,
                'project' => [
                    'id' => $scope->project->id,
                    'name' => $scope->project->name,
                ],
                'tab' => [
                    'id' => $scope->projectTab->id,
                    'name' => $scope->projectTab->name,
                ],
                'access_type' => $scope->access_type->value,
                'valid_to' => $scope->valid_to->toIso8601String(),
            ])
            ->toArray();
    }
}
```

### Bewusst NICHT geshared

Folgende Properties, die im internen `HandleInertiaRequests` geshared werden, dürfen **niemals** ins externe Share:

- `permissionsArray`, `rolesArray`, `permissions` (jsPermissions)
- `auth.user` — komplettes User-Objekt
- Workflow-Flags zur Schichtplanung
- Listen anderer User, Departments, Projekte (außer den explizit freigegebenen via `accessible_scopes`)
- Modul-Settings, die nicht für externe Nutzung gedacht sind

### Hard-Test gegen Leaks

Im Inertia-Middleware-Test (siehe Auth-Foundation-Paket, hier erweitert):

```php
public function test_share_does_not_contain_internal_permissions(): void
{
    $this->actingAsExternalAccess($external);
    $response = $this->get('/external/dashboard');
    $props = $response->inertiaProps();

    $forbidden = ['permissionsArray', 'rolesArray', 'permissions'];
    foreach ($forbidden as $key) {
        $this->assertArrayNotHasKey($key, $props, "Internal key '{$key}' leaked into external share");
    }

    // Recursive scan — falls Properties verschachtelt sind
    $serialized = json_encode($props);
    foreach (['can manage workers', 'artwork admin', 'jsPermissions'] as $needle) {
        $this->assertStringNotContainsString(
            $needle,
            $serialized,
            "Sensitive string '{$needle}' found in external share"
        );
    }
}
```

Der Stringbasierte Check fängt auch obskure Leak-Wege (z.B. wenn jemand versehentlich ein User-Model mit `with('roles')` lädt und das im Share landet).

---

## Schritt 4: `ExternalAppLayout.vue`

Datei: `resources/js/Pages/External/Layouts/ExternalAppLayout.vue`.

Minimal-Variante, kein Chat-Popup, kein Push-Notification-Listener, kein `reloadRolesAndPermissions`:

```vue
<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ title }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="artwork relative min-h-screen bg-zinc-50">
        <ExternalSubMenu />

        <main class="lg:pl-72 pb-20">
            <div class="artwork relative" id="main-content-wrapper">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import ExternalSubMenu from '@/Pages/External/Layouts/ExternalSubMenu.vue'

const { locale } = useI18n()
const page = usePage()

defineProps({
    title: { type: String, default: 'Dashboard' }
})

onMounted(() => {
    if (page.props.locale) {
        document.documentElement.lang = page.props.locale
        locale.value = page.props.locale
    }
})
</script>
```

Vergleich zum internen `AppLayout`: keine `pushNotifications`-Logik, kein `PopupChat`, kein `Echo.private(...).listen(...)`, kein `reloadRolesAndPermissions()`. Bewusst spartanisch.

---

## Schritt 5: `ExternalSubMenu.vue`

Datei: `resources/js/Pages/External/Layouts/ExternalSubMenu.vue`.

Komplett neu geschrieben, NICHT von `SubMenu.vue` abgeleitet — die interne Mainnav hat zu viele projektspezifische Annahmen (Departments, Projects-Filter, Permissions). Statt zu erben, schreiben wir eine bewusst minimale Variante.

Inhalt der externen Mainnav:

1. **Logo** (oben, aus `$page.props.big_logo`)
2. **Eigene Daten** (CRM-Eintrag): Link auf `route('external.crm.show')` mit User-Icon und Display-Name
3. **Freigegebene Projekttabs**: einer pro `accessible_scopes`-Eintrag, gruppiert nach Projekt
4. **Footer-Bereich** (unten): Locale-Switch, Logout-Button

Skelett:

```vue
<template>
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col lg:w-72">
        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-artwork-navigation-background px-6 pb-4">
            <!-- Logo -->
            <div class="flex h-16 shrink-0 items-center">
                <img class="h-8 w-auto" :src="$page.props.big_logo" alt="Logo" />
            </div>

            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <!-- Eigene Daten -->
                    <li>
                        <Link
                            :href="route('external.crm.show')"
                            :class="navItemClasses(route().current('external.crm.show'))"
                        >
                            <PropertyIcon name="IconUser" class="size-6 shrink-0" />
                            {{ $t('My data') }}
                        </Link>
                    </li>

                    <!-- Freigegebene Projekttabs, gruppiert nach Projekt -->
                    <li v-if="groupedScopes.length > 0">
                        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">
                            {{ $t('Shared with you') }}
                        </div>
                        <ul role="list" class="space-y-3">
                            <li v-for="group in groupedScopes" :key="group.project.id">
                                <div class="text-sm font-medium text-zinc-200 mb-1">
                                    {{ group.project.name }}
                                </div>
                                <ul class="ml-2 space-y-1">
                                    <li v-for="scope in group.scopes" :key="scope.id">
                                        <Link
                                            :href="route('external.project.tab', {
                                                project: scope.project.id,
                                                tab: scope.tab.id,
                                            })"
                                            :class="navItemClasses(false /* current detection comes later */)"
                                        >
                                            <span>{{ scope.tab.name }}</span>
                                            <span
                                                v-if="scope.access_type === 'read'"
                                                class="ml-auto text-xs text-zinc-400"
                                            >
                                                {{ $t('read only') }}
                                            </span>
                                        </Link>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <!-- Spacer -->
                    <li class="mt-auto">
                        <div class="text-xs text-zinc-400 mb-2">
                            {{ $t('CRM access valid until') }}:
                            {{ formatDate($page.props.crm_access_expires_at) }}
                        </div>
                        <form @submit.prevent="logout">
                            <button type="submit" class="...logout-styles...">
                                {{ $t('Logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <!-- Mobile-Variante analog, ggf. Schritt-2-Sub-Ticket -->
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const page = usePage()

const groupedScopes = computed(() => {
    const groups = new Map()
    for (const scope of page.props.accessible_scopes ?? []) {
        const key = scope.project.id
        if (!groups.has(key)) {
            groups.set(key, { project: scope.project, scopes: [] })
        }
        groups.get(key).scopes.push(scope)
    }
    return Array.from(groups.values())
})

function logout() {
    router.post(route('external.logout'))
}

function navItemClasses(isCurrent) {
    return [
        'group flex items-center gap-x-3 rounded-md p-2 text-sm font-semibold',
        isCurrent
            ? 'bg-white/10 text-white'
            : 'text-zinc-300 hover:bg-white/5 hover:text-white',
    ]
}

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
```

**Bewusste Auslassungen:**
- Kein `getVisibleSubMenus()`, kein `has_permission`-Check. Die `accessible_scopes` aus dem Share sind bereits gefiltert — Frontend zeigt unkonditional alles, was Backend liefert.
- Kein dynamisches Navigation-Array — Struktur ist hardcoded, weil sie minimal und statisch ist.
- Keine User-Suche, kein Schnellzugriff, kein Notification-Dropdown.

**Hinweis zu `route('external.project.tab')`**: Diese Route existiert nach diesem Paket noch nicht — sie ist Skelett, das im Tab-Sharing-Paket befüllt wird. Hier erstmal als 404/Skelett-Route, damit der Link nicht ins Leere zeigt. Klick auf einen Tab führt vorerst auf eine Stub-Seite "Dieser Tab wird bald verfügbar sein".

---

## Schritt 6: Login-Seite und Magic-Link-UX

### Pages

`resources/js/Pages/External/Auth/Login.vue` — Email-Eingabe, Submit löst `route('external.login.request')` aus.

`resources/js/Pages/External/Auth/LinkSent.vue` — Bestätigungsseite "Wir haben dir einen Link gesendet, falls die Email berechtigt ist". Bewusst generisch (Email-Enumeration-Schutz).

`resources/js/Pages/External/Auth/LinkInvalid.vue` — wird gerendert bei abgelaufenem/genutzten Token. Inhalt: "Dein Link ist ungültig oder abgelaufen. Fordere einen neuen Login-Link an." mit Link zur Login-Form.

`resources/js/Pages/External/Auth/AccessExpired.vue` — wird gerendert nach `CheckExternalAccessValid`-Logout. Inhalt: "Dein Zugriff ist abgelaufen oder wurde entzogen. Sprich mit deiner Ansprechperson, wenn du wieder Zugriff erhalten möchtest."

Diese vier Pages sind eigenständig (kein `ExternalAppLayout`-Wrapper — der Layout enthält ja die Mainnav, die hier nicht gehört). Stattdessen ein dedizierter `ExternalGuestLayout.vue`, der das Centered-Card-Pattern aus dem internen `Login.vue` aufgreift.

### `ExternalGuestLayout.vue`

`resources/js/Pages/External/Layouts/ExternalGuestLayout.vue`. Analog zum Layout im internen `Login.vue` (zwei Spalten, links Form, rechts Banner). Logos und Banner aus `$page.props`.

### Login.vue (Skelett)

```vue
<template>
    <ExternalGuestLayout>
        <div class="space-y-6">
            <h1 class="font-lexend text-2xl font-bold text-zinc-900 tracking-tight">
                {{ $t('Login') }}
            </h1>

            <p class="text-sm text-zinc-600">
                {{ $t('Enter your email and we will send you a login link.') }}
            </p>

            <p v-if="$page.props.flash?.status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
                {{ $page.props.flash.status }}
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    :label="$t('Email') + '*'"
                    type="email"
                    autocomplete="email"
                    required
                />
                <JetInputError :message="errors.email" />

                <BaseUIButton
                    :label="$t('Request login link')"
                    use-translation
                    icon="IconMail"
                    type="submit"
                    :disabled="!form.email || form.processing"
                />
            </form>
        </div>
    </ExternalGuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ExternalGuestLayout from '@/Pages/External/Layouts/ExternalGuestLayout.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import JetInputError from '@/Jetstream/InputError.vue'

const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const form = useForm({ email: '' })

function submit() {
    form.post(route('external.login.request'), {
        onSuccess: () => form.reset('email'),
    })
}
</script>
```

### Backend-Anpassung: Response-Verhalten

Das Auth-Foundation-Paket gibt nach `requestLink` eine generische Response zurück. Hier konkretisieren wir: `redirect()->route('external.login.link-sent')`, eine eigene Inertia-Page. Damit ist die UX klar und das Email-Enumeration-Schutz-Verhalten ist trotzdem gewahrt — die Bestätigungs-Page sagt nichts über die Existenz der Email aus.

Backend-Routen, die das Frontend hier konsumiert:
- `external.login.form` → rendert `Pages/External/Auth/Login.vue`
- `external.login.request` (POST) → redirect zu `external.login.link-sent` (immer, auch bei unbekannter Email)
- `external.login.link-sent` → rendert `Pages/External/Auth/LinkSent.vue`
- `external.login.redeem` → bei Erfolg redirect zu `external.dashboard`, bei Fehler redirect zu `external.login.invalid`
- `external.login.invalid` → rendert `Pages/External/Auth/LinkInvalid.vue`
- `external.access.expired` → rendert `Pages/External/Auth/AccessExpired.vue`

Diese Routen-Skelette werden hier ergänzt (im Auth-Foundation-Paket existierten nur die ersten drei).

---

## Schritt 7: Dashboard- und CRM-Self-Skelett-Pages

Damit die Mainnav nicht ins Leere zeigt, zwei minimale Pages:

### `Pages/External/Dashboard.vue`

Eingangsseite nach Login. Bewusst dünn — zeigt Display-Name, kurze Begrüßung, Übersicht der Scopes.

```vue
<template>
    <ExternalAppLayout :title="$t('Dashboard')">
        <div class="px-8 py-10 max-w-4xl">
            <h1 class="text-2xl font-bold text-zinc-900">
                {{ $t('Welcome back, {name}', { name: $page.props.auth.external.display_name }) }}
            </h1>

            <p class="mt-2 text-sm text-zinc-600">
                {{ $t('Your CRM access is valid until') }}:
                {{ formatDate($page.props.auth.external.crm_access_expires_at) }}
            </p>

            <section class="mt-10" v-if="$page.props.accessible_scopes?.length">
                <h2 class="text-lg font-semibold">{{ $t('Shared with you') }}</h2>
                <ul class="mt-4 space-y-2">
                    <li v-for="scope in $page.props.accessible_scopes" :key="scope.id">
                        <strong>{{ scope.project.name }}</strong>{{ ' — ' }}{{ scope.tab.name }}
                        <span class="text-xs text-zinc-500 ml-2">
                            ({{ scope.access_type === 'write' ? $t('can edit') : $t('read only') }})
                        </span>
                    </li>
                </ul>
            </section>

            <section v-else class="mt-10 text-sm text-zinc-500">
                {{ $t('You have no shared project tabs at the moment.') }}
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import ExternalAppLayout from '@/Pages/External/Layouts/ExternalAppLayout.vue'

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
```

### `Pages/External/Crm/Show.vue`

CRM-Eigenansicht. **Skelett in diesem Paket — read-only, kein Edit-Formular.** Die Edit-Maske (mit Staging-Workflow) kommt im Folgepaket.

Inhalt: Anzeige der gespiegelten CRM-Property-Werte des eigenen CrmContacts (nur nicht-vertrauliche Property-Groups), gruppiert nach `CrmPropertyGroup`. Zeigt: "Bearbeiten kommt in Kürze".

```vue
<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-8 py-10 max-w-4xl">
            <h1 class="text-2xl font-bold text-zinc-900">{{ $t('My data') }}</h1>

            <p class="mt-2 text-sm text-zinc-500">
                {{ $t('Editing will be available soon. For now, you can review the data we have on file.') }}
            </p>

            <section v-for="group in groups" :key="group.id" class="mt-10">
                <h2 class="text-lg font-semibold">{{ group.name }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3">
                    <template v-for="property in group.properties" :key="property.id">
                        <dt class="text-sm font-medium text-zinc-600">{{ property.name }}</dt>
                        <dd class="text-sm text-zinc-900">{{ property.value ?? '—' }}</dd>
                    </template>
                </dl>
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import ExternalAppLayout from '@/Pages/External/Layouts/ExternalAppLayout.vue'

defineProps({
    groups: { type: Array, required: true }
})
</script>
```

### Backend-Skelett für CRM-Show

`ExternalCrmController::show()`:

```php
public function show(Request $request): Response
{
    /** @var ExternalAccess $external */
    $external = $request->user('external');
    $contact = $external->crmContact->load('contactType');

    $groups = CrmPropertyGroup::query()
        ->where('is_confidential', false)
        ->whereHas('properties.contactTypes', fn ($q) => $q->where('id', $contact->contact_type_id))
        ->with(['properties' => function ($q) use ($contact) {
            $q->with(['values' => fn ($vq) => $vq->where('crm_contact_id', $contact->id)]);
        }])
        ->get()
        ->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'properties' => $group->properties->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'value' => $p->values->first()?->value,
            ]),
        ]);

    return Inertia::render('Crm/Show', ['groups' => $groups]);
}
```

**Wichtig:** `where('is_confidential', false)` — die Hard-Rule aus `security-concept.md` Abschnitt 9.1. In diesem Skelett-Paket bereits enforced, weil sonst beim Späteren-Erweitern leicht vergessen.

Routes:

```php
Route::middleware('external')->prefix('external')->name('external.')->group(function () {
    Route::get('dashboard', [ExternalDashboardController::class, 'show'])->name('dashboard');
    Route::get('crm', [ExternalCrmController::class, 'show'])->name('crm.show');
    // Tab-Routen kommen im Tab-Sharing-Paket
});
```

---

## Schritt 8: Routes-Konsolidierung in `routes/external.php`

Im Auth-Foundation-Paket haben wir die externen Routen begonnen. Hier wird die Datei `routes/external.php` zur vollständigen Routen-Definition für externen Zugriff. Registrierung in `app/Providers/RouteServiceProvider.php`:

```php
Route::middleware('external.guest')->name('external.')->prefix('external')->group(base_path('routes/external-guest.php'));
Route::middleware('external')->name('external.')->prefix('external')->group(base_path('routes/external.php'));
```

Trennung in zwei Dateien (`external.php` für authentifizierte, `external-guest.php` für Guest-Bereich) macht das Berechtigungsmodell auf Routen-Ebene direkt lesbar.

---

## Schritt 9: Übersetzungen ergänzen

Neue Übersetzungs-Keys (in `lang/de.json` und `lang/en.json` ergänzen):

```
"My data"
"Shared with you"
"read only"
"can edit"
"CRM access valid until"
"Logout"
"Welcome back, {name}"
"Your CRM access is valid until"
"You have no shared project tabs at the moment."
"Editing will be available soon. For now, you can review the data we have on file."
"Enter your email and we will send you a login link."
"Request login link"
"Login link sent"
"If your email is registered, we have sent you a login link. Please check your inbox."
"The link is invalid or has expired."
"Request a new login link"
"Your access has expired or been revoked."
"Talk to your contact person if you want to regain access."
```

Englische Strings dienen als Key (Projekt-Konvention laut `lang/`-Dateien). Deutsche Strings als Werte.

---

## Schritt 10: Tests

### Vue-Komponenten-Tests

Falls Vitest oder ein anderes Vue-Testing-Setup existiert: Snapshot-Tests für `ExternalSubMenu` mit verschiedenen `accessible_scopes`-States. Wenn das Setup nicht existiert, hier kein neues Testing-Framework einführen — nur Backend-Integrationstests.

### Backend-Integration-Tests

`tests/Feature/ExternalAccess/Frontend/`:

- `ExternalDashboardRendersWithLayout::test_dashboard_uses_app_external_blade_template()` — prüft via Response-Content, dass `app-external` und nicht `app` als Blade verwendet wird (Content enthält `app-external` als Vite-Asset-Reference, NICHT `resources/js/app.js`)
- `ExternalDashboardRendersWithLayout::test_dashboard_props_contain_no_user_permissions()`
- `ExternalSubMenuPropsTest::test_accessible_scopes_are_grouped_by_project_in_share()` — testet das Share-Output, nicht die Vue-Rendering-Logik (die ist im Frontend)
- `ExternalCrmShowTest::test_confidential_property_groups_are_excluded()` — Setup: vertrauliche Group mit Wert für den Contact, prüft dass die nicht in der Response auftaucht
- `ExternalCrmShowTest::test_only_groups_for_contact_type_are_returned()`

### Manifest-Verifikation (CI-Check)

Skript `scripts/verify-external-bundle-isolation.sh` oder `package.json`-Script:

```bash
#!/usr/bin/env bash
# Verify that the external Vite bundle does not contain internal-only modules.
set -e

EXTERNAL_BUNDLE=$(find public/build/assets -name "app-external-*.js" | head -1)
if [ -z "$EXTERNAL_BUNDLE" ]; then
    echo "ERROR: external bundle not found. Did you run 'npm run build'?"
    exit 1
fi

FORBIDDEN_STRINGS=(
    "laravel-permission-to-vuejs"
    "reloadRolesAndPermissions"
    "jsPermissions"
    "PopupChat"
)

FAILED=0
for needle in "${FORBIDDEN_STRINGS[@]}"; do
    if grep -q "$needle" "$EXTERNAL_BUNDLE"; then
        echo "FAIL: external bundle contains forbidden string '$needle'"
        FAILED=1
    fi
done

if [ "$FAILED" -eq 1 ]; then
    echo "External bundle isolation check FAILED."
    exit 1
fi

echo "External bundle isolation check passed."
```

Aufgenommen ins CI als eigener Step nach dem `npm run build`. Schlägt fehl, wenn das externe Bundle auch nur eine Spur internen Permission-Codes enthält.

---

## Acceptance Criteria

- [ ] `vite.config.js` hat zwei Inputs (`app.js`, `app-external.js`)
- [ ] `npm run build` produziert beide Bundles in `public/build/assets/`
- [ ] `public/build/manifest.json` enthält beide Entry-Points
- [ ] `resources/views/app-external.blade.php` existiert, ohne `window.Laravel.jsPermissions` und ohne `auth()->user()`-Aufruf
- [ ] `HandleExternalInertiaRequests::rootView()` liefert `app-external`
- [ ] `resources/js/app-external.js` initialisiert weder `LaravelPermissionToVueJS` noch `window.Echo`
- [ ] `resources/js/bootstrap-external.js` initialisiert Axios ohne Echo/Pusher
- [ ] `ExternalAppLayout.vue` enthält weder `reloadRolesAndPermissions()` noch Push-Notification-Listener
- [ ] `ExternalSubMenu.vue` zeigt nur `accessible_scopes` aus dem Inertia-Share — keine direkten DB-Queries oder weitere Permissions
- [ ] Login-Flow ist end-to-end klickbar: `/external/login` → Email-Eingabe → Bestätigungs-Page (immer dieselbe, egal ob Email existiert) → Link in Mail → Redeem → Dashboard
- [ ] Token-abgelaufen / Token-genutzt führt zu `LinkInvalid.vue`
- [ ] `CheckExternalAccessValid`-Logout führt zu `AccessExpired.vue`
- [ ] Logout aus dem Dashboard funktioniert
- [ ] CRM-Show-Skelett zeigt nur nicht-vertrauliche Property-Groups
- [ ] Bundle-Isolation-Script läuft grün
- [ ] `HandleExternalInertiaRequests`-Tests grün (kein `permissionsArray`/`rolesArray` im Share)
- [ ] Alle in Schritt 10 genannten Integration-Tests grün
- [ ] phpstan, phpcs grün
- [ ] Frontend manuell smoke-getestet im Browser (DDEV): Login-Flow, Dashboard, CRM-Show, Logout, Session-Expiry-Verhalten

---

## Risiken & Edge Cases

**1. Inertia-Asset-Versioning bei zwei Bundles.**
Inertia hat einen Asset-Version-Check (`X-Inertia-Version`-Header) für Cache-Invalidation. Mit zwei Bundles kann es zu Asynchronität kommen: User hat externes Bundle gecacht, internes wurde neu deployed, Version-Hash ändert sich, externer User bekommt 409 Conflict. Mitigation: `HandleExternalInertiaRequests::version()` so überschreiben, dass es nur den Hash des externen Manifest-Eintrags zurückgibt, nicht den globalen. Test-Fall: Frontend-Bundle internal updaten → externe Session darf nicht invalidieren.

**2. Geteiltes CSS-Bundle.**
`app.css` und `global.css` werden in beide Bundles importiert. Wenn das CSS Klassen enthält, die nur intern verwendet werden, ist das harmlos (toter Code). Wenn das CSS aber Selektoren auf internen IDs/Klassen erwartet, die im externen DOM nicht existieren, kein Problem (CSS ohne Match ist no-op). Falls Tailwind-JIT mit `purge`/`content` konfiguriert ist: sicherstellen, dass externe Vue-Files in der `content`-Liste sind, sonst fehlen Klassen im externen Bundle.

**3. PrimeVue + Aura-Theme.**
Aura-Theme wird im internen App registriert. Wenn das externe App dasselbe Theme nutzt, ggf. Konflikte über globale CSS-Variablen. Test im Browser: zwei Tabs offen (intern + extern), Styles dürfen sich nicht gegenseitig überschreiben. Da beide Apps in separaten Bundles laufen, sollte das kein Problem sein — aber kurz verifizieren.

**4. Lang-Files werden geteilt.**
`lang/de.json` und `lang/en.json` werden vom externen Frontend ebenfalls geladen (alles, was in i18n eingespeist wird). Damit liegen alle internen Übersetzungs-Strings auch im externen Browser. Das ist kein Sicherheitsrisiko (Übersetzungs-Keys sind keine Daten), aber Bundle-Größe steigt unnötig. Mitigation **nicht in diesem Paket**, aber zur Kenntnis: später lässt sich pro Bundle ein eigener Lang-Loader bauen, der nur `external/*.json`-Keys lädt.

**5. CSRF-Token-Mismatch zwischen Login und Redeem.**
Magic-Link-Klick (`GET`) erzeugt initial keine externe Session, weil `external.guest`-Middleware nur den Session-Start macht aber noch nicht authentifiziert. Beim Redeem wird die Session erzeugt + authentifiziert. Falls die UX im Frontend einen POST nach dem Redeem macht (z.B. Acknowledge-Click), kommt der CSRF-Token aus dem frisch gestarteten Session. `<meta name="csrf-token">` im Blade-Template muss korrekt aus der aktuellen Request-Session kommen — Standard-Laravel-Verhalten, aber bei der eigenen Session-Cookie-Logik aufpassen, dass es nicht aus dem `web`-Session-Cookie kommt. Testfall: nach Redeem einen POST machen, muss durchgehen.

**6. Vue-DevTools im Production-Build.**
`app-external.js` sollte in Production keine Vue-DevTools-Anbindung haben. Standard-Verhalten von Vue ist „in Production deaktiviert", aber im `setup`-Block setzen wir `app.config.performance = true` nur in DEV — gleiche Logik beim externen Bundle.

**7. Race zwischen `router.on('invalid')` und 401-Antworten.**
Wenn der Backend einen 401 (z.B. abgelaufene Session) zurückgibt, fängt unser Handler den Status und macht `window.location.href = ...`. Wenn der User parallel mehrere Inertia-Requests am Laufen hat, könnten mehrere `invalid`-Events feuern. Mitigation: ein Flag (`isHandlingExpiry = true`) im Handler, das weitere Redirects blockiert.

**8. Logout-CSRF.**
Logout via POST braucht CSRF-Token. Inertia handhabt das normalerweise automatisch über das Meta-Tag — verifizieren im Test.

---

## Was bewusst NICHT Teil dieses Pakets ist

- **CRM-Self-Edit-Maske mit Staging/Approval-Workflow** — eigenes Folgepaket. Hier nur read-only-Skelett.
- **Tab-Inhalte für externe User** — Backend-Endpoints, Frontend-Komponenten-Rendering. Kommt mit dem Scope/Tab-Sharing-Paket.
- **Einladungs-UI im CRM-Index und Projekttab** — anderes Paket, internes Frontend.
- **Notifications-Bell/Push für externe** — bewusst weggelassen (siehe `security-concept.md`: Externe nutzen nur Mail-Channel).
- **Locale-Switcher für externe** — kann später ergänzt werden. Hier wird Locale aus dem internen Default genommen.
- **Mobile-spezifische Mainnav** — Skelett ohne Mobile-Variante. Mobile-Version mit Burger-Menu kommt nachgelagert, sobald die Desktop-UX validiert ist.

---

## Referenzen

- `concept/security-concept.md` — Abschnitt 6.4 (Frontend-Isolation), Abschnitt 9 (vertrauliche Properties)
- Auth-Foundation-Paket — vorhergehende Stufe
- `resources/js/app.js` — interne Entry-Point-Vorlage
- `resources/views/app.blade.php` — interne Blade-Vorlage
- `resources/js/Layouts/AppLayout.vue` — internes Layout (Vergleichsbasis)
- `resources/js/Layouts/SubMenu.vue` — interne Mainnav (Vergleichsbasis)
- `resources/js/Pages/Auth/Login.vue` — Style-Vorlage für externes Login
- `artwork/Modules/ExternalAccess/Http/Middleware/HandleExternalInertiaRequests.php` — wird hier zur finalen Form ausgebaut
