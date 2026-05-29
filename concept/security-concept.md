# Arbeitspaket: Auth-Foundation für externen Zugriff

## Kontext

Dieses Paket implementiert die Backend-Grundlage für externen Zugriff auf CRM und Projekttabs, wie in `concept/security-concept.md` spezifiziert. Das Paket umfasst:

- Datenmodell (4 neue Tabellen, 4 Models, Factories)
- Guard-Setup (`external` Guard, eigenes Session-Cookie)
- Magic-Link-Login-Flow (Token-Generierung, Mail-Versand, Token-Einlösung, Logout)
- Middleware-Pipeline (`external` Middleware-Group, Validity-Check)
- Throttling und Sicherheits-Schutz (Email-Enumeration-Schutz, Rate-Limits)

**Nicht Teil dieses Pakets:**
- Frontend (eigener Vue-Entry-Point + externes Layout) → eigenes Folgepaket
- Einladungs-Flow (Service + UI) → folgt nach Frontend-Paket
- Staging/Approval-Workflow für Source-Entity-Edits → späteres Paket
- Notifications für externe Empfänger → späteres Paket
- Erweiterte Scope-Auflösung (Projekt/Tab) → späteres Paket
- Frontend-Inertia-Share-Logik → wird im Frontend-Paket konsumiert, aber die `HandleExternalInertiaRequests`-Middleware-Klasse als Skelett entsteht hier

**Voraussetzung:**
Das Arbeitspaket "Ablösung Antonrom durch Spatie ActivityLog" ist gemerged. `spatie/laravel-activitylog` ist produktiv und kann `ExternalAccess` als polymorphen `causer` aufnehmen (Spatie-Standard, keine zusätzlichen Anpassungen nötig).

## Vorarbeit

- Lies `concept/security-concept.md` vollständig, insbesondere Abschnitte 2, 3, 6 und 10.
- Lies `concept/auth.md` zur Einordnung des bestehenden Auth-Stacks.
- Lies `CLAUDE.md` für Projektkonventionen (DDEV, Modular-Monolith, Service/Repository-Pattern, phpstan/phpcs, i18n).
- Lies `artwork/Modules/Invitation/Services/InvitationService.php` als Referenz für Token-Pattern (Plain + Hash via `HashManager`).
- Branch von `dev`: `feat/external-access-auth-foundation`.

## Modulstruktur (neu)

```
artwork/Modules/ExternalAccess/
├── Models/
│   ├── ExternalAccess.php
│   ├── ExternalAccessScope.php
│   ├── ExternalLoginToken.php
│   └── ExternalInvitation.php
├── Repositories/
│   ├── ExternalAccessRepository.php
│   └── ExternalLoginTokenRepository.php
├── Services/
│   ├── ExternalAccessService.php       ← Resolver (findByEmail etc.) — Logik kommt schrittweise
│   └── ExternalLoginService.php        ← Magic-Link-Flow: requestLink, redeemToken, logout
├── Http/
│   ├── Controllers/
│   │   └── ExternalLoginController.php
│   ├── Middleware/
│   │   ├── CheckExternalAccessValid.php
│   │   └── HandleExternalInertiaRequests.php   ← Skelett, finaler Share kommt im Frontend-Paket
│   └── Requests/
│       ├── RequestLoginLinkRequest.php
│       └── RedeemLoginTokenRequest.php
├── Notifications/
│   └── ExternalLoginLinkNotification.php
├── Providers/
│   └── ExternalAccessServiceProvider.php
└── Console/
    └── CleanupExpiredLoginTokensCommand.php
```

Migrations bleiben im Standard-Laravel-Verzeichnis: `database/migrations/`.

Factories liegen in `database/factories/ExternalAccess/`.

Tests in `tests/Feature/ExternalAccess/` und `tests/Unit/ExternalAccess/`.

---

## Schritt 1: Datenmodell

### Migrations

Vier Migrations in dieser Reihenfolge anlegen (Dateinamen mit aufsteigenden Timestamps):

**`create_external_accesses_table.php`**

```php
Schema::create('external_accesses', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->foreignId('crm_contact_id')->constrained()->cascadeOnDelete();
    $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('crm_access_expires_at')->nullable();
    $table->timestamp('revoked_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();

    $table->index(['email', 'revoked_at']);
    $table->index('crm_access_expires_at');
});
```

**`create_external_access_scopes_table.php`**

```php
Schema::create('external_access_scopes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('project_tab_id')->constrained()->cascadeOnDelete();
    $table->enum('access_type', ['read', 'write']);
    $table->timestamp('valid_from');
    $table->timestamp('valid_to');
    $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index(['external_access_id', 'valid_to']);
    $table->unique(['external_access_id', 'project_tab_id']);  // additive Scopes: kein Duplikat pro Tab
});
```

**`create_external_login_tokens_table.php`**

```php
Schema::create('external_login_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
    $table->string('token_hash', 64)->unique();  // sha256 hex
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->string('ip_address', 45)->nullable();       // Audit beim Token-Anfordern
    $table->string('user_agent')->nullable();
    $table->timestamps();

    $table->index('expires_at');
    $table->index(['external_access_id', 'used_at']);
});
```

**`create_external_invitations_table.php`**

```php
Schema::create('external_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('external_access_id')->constrained('external_accesses')->cascadeOnDelete();
    $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
    $table->enum('source', ['crm_index', 'project_tab']);
    $table->unsignedBigInteger('source_reference_id')->nullable();   // project_id bei project_tab
    $table->timestamp('email_sent_at');
    $table->timestamp('first_redeemed_at')->nullable();
    $table->timestamps();

    $table->index('external_access_id');
});
```

### Models

**`artwork/Modules/ExternalAccess/Models/ExternalAccess.php`**

- Erweitert `Artwork\Core\Database\Models\Model` (siehe Projektkonvention in `CLAUDE.md`)
- Implementiert `Illuminate\Contracts\Auth\Authenticatable`
- Implementiert `Illuminate\Contracts\Notifications\Notifiable` (oder nutzt das Trait `Notifiable`)
- **Nutzt explizit nicht:** `HasRoles`, `HasPermissions`, `HasApiTokens`
- Implementiert die `Authenticatable`-Methoden: `getAuthIdentifierName()`, `getAuthIdentifier()`, `getAuthPassword()` (gibt leeren String zurück — Magic-Link only, kein Passwort), `getRememberToken()`, `setRememberToken()`, `getRememberTokenName()`. Remember-Token-Spalte gibt es nicht, also gibt `getRememberToken()` `null` zurück und `setRememberToken()` ist no-op. Diese explizite Implementierung ist wichtig, um klar zu signalisieren: hier wird bewusst kein Remember-Me-Mechanismus angeboten.
- `fillable`: `['email', 'crm_contact_id', 'invited_by_user_id', 'crm_access_expires_at']`
- Casts: `crm_access_expires_at`, `revoked_at`, `last_login_at` als `datetime`
- Relations:
    - `crmContact(): BelongsTo` → `CrmContact`
    - `invitedBy(): BelongsTo` → `User`
    - `scopes(): HasMany` → `ExternalAccessScope`
    - `loginTokens(): HasMany` → `ExternalLoginToken`
    - `invitations(): HasMany` → `ExternalInvitation`
- Hilfsmethoden:
    - `hasAnyActiveAccess(): bool` — true wenn `revoked_at === null` UND (`crm_access_expires_at > now()` ODER mindestens ein `ExternalAccessScope` mit `valid_to > now()`)
    - `isCrmAccessActive(): bool`
    - `routeNotificationForMail(): string` → gibt `$this->email` zurück

**`ExternalAccessScope.php`**

- Standard-Eloquent-Model
- Casts: `valid_from`, `valid_to` → `datetime`; `access_type` → `string` (oder String-Enum, siehe unten)
- Relations: `externalAccess`, `project`, `projectTab` (alle BelongsTo)
- Scopes: `currentlyValid()` — `where('valid_from', '<=', now())->where('valid_to', '>=', now())`

**`ExternalLoginToken.php`**

- Standard-Eloquent-Model
- Casts: `expires_at`, `used_at` → `datetime`
- Relations: `externalAccess()` (BelongsTo)
- Hilfsmethoden:
    - `isValid(): bool` — `used_at === null && expires_at > now()`
- **Sicherheits-Hinweis:** `token_hash` darf niemals via `$fillable` oder `$casts` zugänglich gemacht werden für UI-Auslieferung. Bei `toArray()`/`toJson()` über `$hidden` ausschließen.

**`ExternalInvitation.php`**

- Standard-Eloquent-Model
- Casts: `email_sent_at`, `first_redeemed_at` → `datetime`
- Relations: `externalAccess`, `invitedBy` (User)

### Enum für `access_type`

`artwork/Modules/ExternalAccess/Enums/ExternalAccessType.php`:

```php
enum ExternalAccessType: string
{
    case READ = 'read';
    case WRITE = 'write';
}
```

Im `ExternalAccessScope`-Model casten: `'access_type' => ExternalAccessType::class`.

### Factories

Für alle vier Models in `database/factories/ExternalAccess/`. Default-States:

- `ExternalAccessFactory::active()` — `crm_access_expires_at` in 12 Monaten, `revoked_at = null`
- `ExternalAccessFactory::revoked()` — `revoked_at = now()`
- `ExternalAccessFactory::crmAccessExpired()` — `crm_access_expires_at` vor 1 Tag
- `ExternalLoginTokenFactory::valid()` — `expires_at` in 15 Min, `used_at = null`
- `ExternalLoginTokenFactory::expired()` — `expires_at` vor 1 Min
- `ExternalLoginTokenFactory::used()` — `used_at = now()`

### Tests (Schritt 1)

Unit-Tests in `tests/Unit/ExternalAccess/Models/`:

- `ExternalAccessTest::hasAnyActiveAccess_returns_false_when_revoked()`
- `ExternalAccessTest::hasAnyActiveAccess_returns_true_with_crm_access()`
- `ExternalAccessTest::hasAnyActiveAccess_returns_true_with_active_scope()`
- `ExternalAccessTest::hasAnyActiveAccess_returns_false_when_all_expired()`
- `ExternalLoginTokenTest::isValid_returns_false_for_expired_token()`
- `ExternalLoginTokenTest::isValid_returns_false_for_used_token()`

---

## Schritt 2: Guard-Setup

### `config/auth.php`

Erweiterung:

```php
'guards' => [
    'web' => [...],     // unverändert
    'api' => [...],     // unverändert (Passport)
    'sanctum' => [...], // unverändert
    'external' => [
        'driver' => 'session',
        'provider' => 'externals',
    ],
],

'providers' => [
    'users' => [...],   // unverändert
    'externals' => [
        'driver' => 'eloquent',
        'model' => \Artwork\Modules\ExternalAccess\Models\ExternalAccess::class,
    ],
],
```

Kein Password-Reset-Provider — es gibt kein Passwort.

### Eigene Session-Konfiguration

`config/session.php` selbst nicht ändern (das ist global). Stattdessen in `ExternalAccessServiceProvider::boot()` zur Laufzeit den Cookie-Namen für den externen Guard überschreiben — oder besser: über eine **eigene Middleware** den Session-Cookie-Namen für externe Routen swappen. Empfehlung:

In `ExternalAccessServiceProvider::register()` einen neuen Config-Key registrieren:

```php
$this->mergeConfigFrom(__DIR__.'/../../../../config/external_access.php', 'external_access');
```

Neue Config-Datei `config/external_access.php`:

```php
return [
    'session' => [
        'cookie' => env('EXTERNAL_SESSION_COOKIE', 'artwork_external_session'),
        'lifetime' => env('EXTERNAL_SESSION_LIFETIME_MINUTES', 120),    // 2h Idle
        'expire_on_close' => true,
    ],
    'login_token' => [
        'lifetime_minutes' => 15,
    ],
    'absolute_session_lifetime_minutes' => 480,  // 8h Hard-Cap
    'rate_limits' => [
        'request_link_per_email_per_hour' => 3,
        'request_link_per_ip_per_hour' => 10,
        'redeem_token_per_ip_per_minute' => 10,
        'general_per_external_per_minute' => 30,
    ],
];
```

Werte sind die Defaults aus `security-concept.md`. Konfigurierbarkeit gewährleistet, dass Hausverwaltungen später anpassen können (Admin-UI für globale Settings kommt in einem späteren Paket — hier reicht ENV-basierte Konfig).

Die effektive Session-Cookie-Umschaltung auf externen Routen passiert in der `external`-Middleware-Group (siehe Schritt 4) durch eine eigene Middleware `SwapExternalSessionCookie` oder durch dynamisches `Config::set('session.cookie', config('external_access.session.cookie'))` **vor** `StartSession`. Beide Wege akzeptabel — `Config::set` vor `StartSession` ist der pragmatischste Weg ohne neue Custom-Middleware.

### `AuthServiceProvider` — Schutz gegen `Gate::before()`-Bypass

In `app/Providers/AuthServiceProvider.php` ist der Admin-Bypass aktiv:

```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
        return true;
    }
});
```

**Risiko:** `Gate::before()` bekommt jedes `Auth::user()`, egal von welchem Guard. Wenn ein `ExternalAccess`-Objekt rein-rutscht und ohne Typ-Check `$user->hasRole(...)` aufgerufen wird, gibt es einen Fatal Error (`ExternalAccess` hat keine `hasRole`-Methode). Schlimmer wäre, wenn `ExternalAccess` versehentlich einen `hasRole`-Stub hätte, der true zurückgibt.

**Lösung:** Type-Check ergänzen:

```php
Gate::before(function ($user, $ability) {
    if (!$user instanceof \Artwork\Modules\User\Models\User) {
        return null;  // Externe und andere Identities laufen normal durch Policies
    }
    if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
        return true;
    }
    return null;
});
```

Diese Änderung ist defensiv und betrifft den internen User-Bypass nicht. Hat sich aber als notwendig erwiesen, weil das Konzept einen klaren Cut zwischen User-Welt und External-Welt verlangt. Im Test (siehe unten) wird verifiziert, dass ein `ExternalAccess`-User nicht versehentlich Admin-Rechte bekommt.

### `ExternalAccessServiceProvider`

Neue Datei `artwork/Modules/ExternalAccess/Providers/ExternalAccessServiceProvider.php`. Registriert:

- Config-Merge (siehe oben)
- Rate-Limiter (siehe Schritt 3.4)
- Notification-Channel falls separat konfiguriert (Standard Mail reicht)

Registrierung in `config/app.php`/`bootstrap/providers.php` (was das Projekt nutzt — bestehende Provider als Referenz).

### Tests (Schritt 2)

`tests/Feature/ExternalAccess/Auth/GuardConfigurationTest.php`:

- `external_guard_is_registered()`
- `external_guard_uses_external_access_model()`
- `external_session_cookie_differs_from_web()`

`tests/Feature/ExternalAccess/Auth/AdminBypassProtectionTest.php`:

- `external_access_user_does_not_trigger_admin_gate_bypass()` — authentifiziert sich als ExternalAccess via Guard und prüft mit einer Test-Gate, dass `Gate::before()` nicht greift
- `internal_admin_still_triggers_gate_bypass()` — Regression-Test, dass interner Admin-Bypass weiterhin funktioniert

---

## Schritt 3: Magic-Link-Login-Flow

### Token-Lifecycle

**Generierung (`ExternalLoginService::requestLoginLink`):**

1. Eingabe: `email`, IP, User-Agent
2. Lookup `ExternalAccess` per Email
3. Wenn nicht gefunden ODER `revoked_at !== null` ODER `!hasAnyActiveAccess()` → **silent return**. Generic Response wird vom Controller geliefert (siehe unten). Diese Stille verhindert Email-Enumeration.
4. Wenn gefunden und gültig:
    - Plain-Token: `Str::random(64)` (URL-safe Random String)
    - Hash: `hash('sha256', $plainToken)` (NICHT `Hash::make()` — wir brauchen deterministischen Lookup beim Einlösen)
    - Neuen `ExternalLoginToken` anlegen mit `token_hash`, `expires_at = now()->addMinutes(15)`, IP, User-Agent
    - `ExternalLoginLinkNotification` via Mail-Channel senden (queued)
5. Plain-Token wird NIRGENDS persistiert (kein Log, kein DB), nur in der versendeten Mail.

**Einlösung (`ExternalLoginService::redeemToken`):**

1. Eingabe: Plain-Token aus URL
2. `hash('sha256', $plainToken)` berechnen
3. `ExternalLoginToken::where('token_hash', $hash)->first()` — einziger DB-Hit
4. Validierung in dieser Reihenfolge (alle Fehler liefern dieselbe generische Antwort "Link ungültig oder abgelaufen"):
    - Token existiert
    - `isValid()` true (nicht used, nicht expired)
    - `externalAccess->revoked_at === null`
    - `externalAccess->hasAnyActiveAccess()` true
5. Wenn valid: in einer Transaktion
    - `token->used_at = now()`
    - `externalAccess->last_login_at = now()`
    - Falls erste Einlösung: zugehörige `ExternalInvitation->first_redeemed_at = now()`
    - `Auth::guard('external')->login($externalAccess)` (Laravel-Standard, schreibt Session)
    - `session()->regenerate()` (CSRF-Token frisch)
6. Return: Redirect-Ziel (kommt vom Frontend-Paket — hier erstmal Default-Route `/external/dashboard`, die noch nicht existiert)

### Notification

`ExternalLoginLinkNotification`:

- Channels: `['mail']`
- `toMail($notifiable)`:
    - Plain-Token kommt als Konstruktor-Parameter rein
    - URL: `route('external.login.redeem', ['token' => $plainToken])`
    - Mail-Template: `resources/views/emails/external_login_link.blade.php` — analog zum bestehenden `password_reset.blade.php`-Pattern
    - Inhalt (Übersetzungen in `de.json`/`en.json`):
        - Subject: "Dein Login-Link für [Unternehmensname]"
        - Body: erklärt was passiert ist, Link, Hinweis "Link gültig für 15 Minuten", Hinweis "Wenn du das nicht angefordert hast, ignoriere diese Mail"
    - Sender/Absender wie bei Password-Reset über `GeneralSettings::business_email` / Page-Title (siehe `FortifyServiceProvider` als Vorlage)

### Controller und Routes

**`ExternalLoginController`:**

```php
public function showLoginForm(): Response { /* Inertia-View — Frontend-Paket befüllt das. Hier Skelett. */ }
public function requestLink(RequestLoginLinkRequest $request): Response { /* generische Response */ }
public function redeem(string $token, RedeemLoginTokenRequest $request): RedirectResponse { /* siehe redeemToken-Flow */ }
public function logout(): RedirectResponse { /* Auth::guard('external')->logout(); session()->invalidate(); */ }
```

**Routes** in `routes/web.php` (oder neue Datei `routes/external.php`, die in `RouteServiceProvider` registriert wird — letzteres ist sauberer und macht den Diff im Web-Router-File überschaubarer):

```php
Route::middleware('external.guest')->prefix('external')->name('external.')->group(function () {
    Route::get('login', [ExternalLoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('login', [ExternalLoginController::class, 'requestLink'])
        ->middleware('throttle:external-request-link')
        ->name('login.request');
    Route::get('login/{token}', [ExternalLoginController::class, 'redeem'])
        ->middleware('throttle:external-redeem-token')
        ->name('login.redeem');
});

Route::middleware('external')->prefix('external')->name('external.')->group(function () {
    Route::post('logout', [ExternalLoginController::class, 'logout'])->name('logout');
    // Weitere externe Routen kommen in späteren Paketen
});
```

`external.guest`-Middleware: leitet einen bereits angemeldeten externen User auf das Dashboard um (analog zu Laravel's `guest`-Middleware, aber für `external`-Guard).

`external`-Middleware: die Middleware-Group aus Schritt 4.

### Request-Validation

`RequestLoginLinkRequest::rules()`:
```php
['email' => ['required', 'email:rfc']]
```

`RedeemLoginTokenRequest::rules()`:
```php
['token' => ['required', 'string', 'size:64']]
```

### Rate-Limiter

In `ExternalAccessServiceProvider::boot()`:

```php
RateLimiter::for('external-request-link', function (Request $request) {
    $email = strtolower((string) $request->input('email'));
    return [
        Limit::perHour(config('external_access.rate_limits.request_link_per_email_per_hour'))
            ->by('external-link:email:' . $email),
        Limit::perHour(config('external_access.rate_limits.request_link_per_ip_per_hour'))
            ->by('external-link:ip:' . $request->ip()),
    ];
});

RateLimiter::for('external-redeem-token', function (Request $request) {
    return Limit::perMinute(config('external_access.rate_limits.redeem_token_per_ip_per_minute'))
        ->by('external-redeem:ip:' . $request->ip());
});

RateLimiter::for('external', function (Request $request) {
    $external = $request->user('external');
    if ($external) {
        return Limit::perMinute(config('external_access.rate_limits.general_per_external_per_minute'))
            ->by('external:' . $external->id);
    }
    return Limit::perMinute(20)->by($request->ip());
});
```

### Cleanup-Command

`CleanupExpiredLoginTokensCommand` (`changes:cleanup-expired-tokens` als Signature — nein, besser: `external:cleanup-tokens`):

- Löscht abgelaufene und eingelöste Tokens, die älter als 24h sind
- Bleibt in `app/Console/Kernel.php` schedule-bar (täglich)
- Idempotent, kein State

### Tests (Schritt 3)

`tests/Feature/ExternalAccess/Auth/RequestLoginLinkTest.php`:

- `unknown_email_returns_generic_response_without_sending_mail()`
- `revoked_external_returns_generic_response_without_sending_mail()`
- `expired_external_returns_generic_response_without_sending_mail()`
- `valid_external_receives_mail_with_token()`
- `request_link_throttled_after_3_attempts_per_hour_per_email()`
- `request_link_throttled_after_10_attempts_per_hour_per_ip()`
- `plain_token_never_appears_in_database()` — DB-Snapshot vor und nach Request, kein Feld enthält den Plain-Token
- `plain_token_never_appears_in_logs()` — Log-Mock, prüft dass kein Log-Eintrag den Plain-Token enthält

`tests/Feature/ExternalAccess/Auth/RedeemTokenTest.php`:

- `valid_token_authenticates_external_user()`
- `expired_token_returns_generic_error_and_does_not_authenticate()`
- `used_token_returns_generic_error_and_does_not_authenticate()`
- `revoked_user_token_returns_generic_error_even_with_valid_token()`
- `redeeming_token_marks_used_at_and_updates_last_login_at()`
- `redeeming_first_token_sets_first_redeemed_at_on_invitation()`
- `redeeming_token_regenerates_session()` — verhindert Session-Fixation
- `redeem_token_throttled_after_10_attempts_per_minute_per_ip()`

`tests/Feature/ExternalAccess/Auth/LogoutTest.php`:

- `logout_invalidates_session()`
- `logout_redirects_to_login()`

---

## Schritt 4: Middleware-Pipeline

### `external` Middleware-Group

In `app/Http/Kernel.php` `$middlewareGroups`:

```php
'external' => [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Artwork\Modules\ExternalAccess\Http\Middleware\SwapExternalSessionConfig::class,  // siehe unten
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \Artwork\Modules\ExternalAccess\Http\Middleware\Authenticate::class,                // verifiziert external-Guard
    \Artwork\Modules\ExternalAccess\Http\Middleware\CheckExternalAccessValid::class,
    \Artwork\Modules\ExternalAccess\Http\Middleware\HandleExternalInertiaRequests::class,
    \Artwork\Modules\Localization\Localization::class,
],

'external.guest' => [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Artwork\Modules\ExternalAccess\Http\Middleware\SwapExternalSessionConfig::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \Artwork\Modules\ExternalAccess\Http\Middleware\RedirectIfAuthenticatedExternal::class,
    \Artwork\Modules\Localization\Localization::class,
],
```

**Bewusst weggelassen vs. `web`-Group:**
- `AuthenticateSession` (Jetstream) — relevant nur für `web`-Guard
- `HandleInertiaRequests` (Standard) — leakt Permissions/Rollen, eigene Variante stattdessen
- `UpdateUserStatus`, `ModuleSettingsMiddleware`, `SetDeveloperEnvironment` — interne Konzepte

### Einzelne Middlewares

**`SwapExternalSessionConfig`:**

Setzt zur Laufzeit `Config::set('session.cookie', config('external_access.session.cookie'))` und Lifetime, **bevor** `StartSession` die Cookie-Logik auswertet. Macht den separaten Session-Cookie ohne globalen Config-Eingriff möglich.

```php
public function handle(Request $request, Closure $next): Response
{
    Config::set('session.cookie', config('external_access.session.cookie'));
    Config::set('session.lifetime', config('external_access.session.lifetime'));
    Config::set('session.expire_on_close', config('external_access.session.expire_on_close'));
    return $next($request);
}
```

**`Authenticate`:**

Eigene Variante von Laravel's `Authenticate`, die NUR den `external`-Guard prüft:

```php
public function handle(Request $request, Closure $next): Response
{
    if (!Auth::guard('external')->check()) {
        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated'], 401)
            : redirect()->route('external.login.form');
    }
    Auth::shouldUse('external');
    return $next($request);
}
```

`Auth::shouldUse('external')` setzt den default-Guard für den restlichen Request-Lifecycle — sehr wichtig, damit `Auth::user()`, `$request->user()` etc. nicht versehentlich den `web`-Guard treffen.

**`CheckExternalAccessValid`:**

```php
public function handle(Request $request, Closure $next): Response
{
    /** @var ExternalAccess $external */
    $external = Auth::guard('external')->user();

    if ($external->revoked_at !== null || !$external->hasAnyActiveAccess()) {
        Auth::guard('external')->logout();
        $request->session()->invalidate();
        return redirect()
            ->route('external.login.form')
            ->with('status', __('Your access has expired or been revoked.'));
    }

    return $next($request);
}
```

**`RedirectIfAuthenticatedExternal`:**

```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::guard('external')->check()) {
        return redirect()->route('external.dashboard');
    }
    return $next($request);
}
```

Route `external.dashboard` existiert in diesem Paket noch nicht — Skelett-Route anlegen, die einen 200-Stub-Response liefert. Echte Implementierung kommt im Frontend-Paket.

**`HandleExternalInertiaRequests`:**

Erbt von `Inertia\Middleware`. In diesem Paket nur als Skelett mit absolutem Minimum-Share — finale Anreicherung (accessible_scopes etc.) kommt im Frontend-Paket:

```php
public function share(Request $request): array
{
    /** @var ExternalAccess|null $external */
    $external = $request->user('external');

    return array_merge(parent::share($request), [
        'auth' => [
            'external' => $external ? [
                'id' => $external->id,
                'display_name' => $external->crmContact->display_name,
            ] : null,
        ],
    ]);
}
```

**Wichtig:** **niemals** `parent::share($request)` so erweitern, dass es Permissions, Rollen oder andere intern-User-Properties leakt. Wenn die Eltern-Inertia-Middleware bereits etwas sharen sollte, das wir nicht wollen, in diesem Modul `parent::share()` durch explizite Auswahl ersetzen. Hier ggf. überprüfen, was `Inertia\Middleware::share()` standardmäßig liefert (i.d.R. nichts Sensibles, aber zur Sicherheit Test schreiben).

### Tests (Schritt 4)

`tests/Feature/ExternalAccess/Middleware/`:

- `CheckExternalAccessValidTest::revoked_external_is_logged_out_on_next_request()`
- `CheckExternalAccessValidTest::expired_external_is_logged_out_on_next_request()`
- `CheckExternalAccessValidTest::active_external_passes_through()`
- `SwapExternalSessionConfigTest::external_session_cookie_is_used_for_external_routes()`
- `SwapExternalSessionConfigTest::web_session_cookie_remains_unchanged_for_web_routes()`
- `HandleExternalInertiaRequestsTest::share_does_not_contain_permissions_or_roles()` — explizit prüfen: keine Properties wie `permissionsArray`, `rolesArray`, `permissions`
- `HandleExternalInertiaRequestsTest::share_contains_only_minimal_auth_info()`
- `AuthenticateTest::unauthenticated_request_redirects_to_login()`
- `AuthenticateTest::shouldUse_external_is_set_for_authenticated_requests()` — verifiziert dass `Auth::user()` ohne expliziten Guard das ExternalAccess-Objekt liefert
- `RedirectIfAuthenticatedExternalTest::authenticated_external_is_redirected_from_login_form()`

### Session-Cookie-Isolation — Integration Test

`tests/Feature/ExternalAccess/Session/SessionIsolationTest.php`:

- `external_session_does_not_authenticate_web_guard()` — externe Session aufmachen, dann eine Web-Route besuchen, muss als unauthenticated landen
- `web_session_does_not_authenticate_external_guard()` — umgekehrt
- `parallel_sessions_in_same_browser_work_independently()` — beide Cookies parallel im Cookie-Jar, beide Guards funktionieren unabhängig

Dieser Test ist der wichtigste Sicherheits-Smoke-Test des ganzen Pakets — falls hier was bricht, ist das gesamte Trust-Modell kompromittiert.

---

## Acceptance Criteria

- [ ] Vier Migrations laufen sauber durch (`migrate:fresh --seed` ohne Errors)
- [ ] Vier Models existieren in `artwork/Modules/ExternalAccess/Models/` mit funktionierenden Relations, Casts, Factories
- [ ] `ExternalAccess` implementiert `Authenticatable` ohne `HasRoles`/`HasPermissions`/`HasApiTokens`
- [ ] `config/auth.php` kennt den `external`-Guard und Provider
- [ ] `config/external_access.php` existiert mit konfigurierbaren Defaults
- [ ] `AuthServiceProvider::Gate::before()` ist mit Type-Check abgesichert; Regression-Test grün
- [ ] Magic-Link-Flow funktioniert end-to-end:
    - [ ] Request mit unbekannter Email liefert generische Antwort ohne Mail-Versand
    - [ ] Request mit gültiger Email versendet eine Mail mit funktionierendem Link
    - [ ] Link-Einlösung authentifiziert den externen User
    - [ ] Token ist single-use und 15min lebenslang
    - [ ] Plain-Token ist nirgends in DB oder Logs
- [ ] `external`-Middleware-Group ist registriert und nutzt eigenen Session-Cookie
- [ ] Web- und External-Sessions sind isoliert (SessionIsolationTest grün)
- [ ] `HandleExternalInertiaRequests` shared keine Permissions/Rollen/internal-User-Daten
- [ ] Rate-Limits greifen: 3/h Email, 10/h IP für Link-Request; 10/min/IP für Redeem
- [ ] Cleanup-Command `external:cleanup-tokens` löscht abgelaufene/genutzte Tokens > 24h
- [ ] phpstan, phpcs grün
- [ ] Alle in den Schritten genannten Tests grün

---

## Risiken & Edge Cases

**1. `Auth::user()` ohne expliziten Guard liefert immer den default-Guard.**
Wenn vergessen wird, `Auth::shouldUse('external')` in der `Authenticate`-Middleware zu setzen, kann ein Controller in einer externen Route versehentlich `Auth::user()` aufrufen und kriegt `null` oder schlimmer einen Web-User. Test `AuthenticateTest::shouldUse_external_is_set_for_authenticated_requests` deckt das ab. Alle externen Controller-Methoden idealerweise explizit `$request->user('external')` verwenden — defensive Konvention im Modul, in PR-Reviews darauf achten.

**2. Session-Cookie-Swap-Reihenfolge.**
`SwapExternalSessionConfig` MUSS vor `StartSession` laufen. Falsche Reihenfolge → Web-Cookie wird auch für externe Routen verwendet. Reihenfolge der Middleware-Group ist hier kritisch — phpstan kann das nicht catchen, also Tests darauf.

**3. Email-Enumeration durch Timing-Attacken.**
Auch wenn die Response generisch ist, kann ein Angreifer Latenz messen: Bekannte Email → DB-Hit + Mail-Versand-Queue (langsamer) vs. unbekannte Email → schneller. Mitigation: Mail-Versand IMMER queued (nie synchron), kein expliziter Sleep — die Queue-Anfrage selbst hat ähnliche Latenz wie ein synchroner DB-Hit. Akzeptabel für v1; perfekte Constant-Time-Response ist mit Laravel ohne Zusatzaufwand schwer zu garantieren.

**4. `revoked_at` und parallele aktive Sessions.**
Wenn ein Admin einen externen Zugriff revokt, läuft eine bereits offene Session des Externen so lange, bis die nächste Request `CheckExternalAccessValid` durchläuft. Das ist akzeptabel (Standard-Verhalten von Session-basierter Auth), aber für extreme Fälle (sofortiger Cut) wäre Session-Storage-Lookup nötig. Aus v1 raushalten, in `concept/security-concept.md` Abschnitt "Bewusst nicht im Scope" verankert.

**5. CSRF auf `external/login` POST.**
Der Request-Link-POST braucht CSRF-Token. Vor dem Login gibt es noch keine `external`-Session, der CSRF-Token kommt aus der `external.guest`-Middleware-Group. Funktioniert, sofern Frontend dem Login-Endpoint einen frischen Token mitgibt — wichtig für Frontend-Paket.

**6. `crm_contact_id` constrained → wenn CrmContact gelöscht wird, kaskadiert ExternalAccess.**
`cascadeOnDelete()` ist bewusst gewählt: ohne CrmContact gibt es keinen sinnvollen externen Zugriff. Falls in der Praxis CrmContacts archiviert statt gelöscht werden sollten, wäre `SoftDeletes` auf CrmContact die richtige Stelle — nicht hier. Keine Änderung in diesem Paket.

**7. Mail-Versand-Fehler.**
Wenn die Queue-Mail fehlschlägt (z.B. SMTP down), bleibt der Token in der DB liegen und läuft nach 15 Min ab. Der User sieht keine Fehlermeldung — Standard-Generic-Response. Das ist beabsichtigt (Enumeration-Schutz), aber Operations sollte Mail-Failure-Monitoring auf der Queue haben. Hinweis dazu in der README ergänzen.

**8. Race bei Token-Einlösung.**
Wenn ein User den Link 2x parallel klickt: beide Requests laden denselben Token aus der DB, beide validieren ihn (`used_at === null`), beide setzen `used_at = now()`. Theoretisch könnten beide durchgehen.
Mitigation: Token-Einlösung in einer Transaktion mit `lockForUpdate()`:
```php
DB::transaction(function () use ($hash) {
    $token = ExternalLoginToken::where('token_hash', $hash)->lockForUpdate()->first();
    if (!$token || !$token->isValid()) { abort(403); }
    $token->update(['used_at' => now()]);
    // ... weitere Schritte
});
```
Test `redeem_token_can_only_be_used_once_under_concurrent_requests()` ergänzen.

---

## Was bewusst NICHT Teil dieses Pakets ist

- **Frontend (Vue-Entry-Point, ExternalAppLayout, ExternalSubMenu, Login-Form-UI)** — eigenes Folgepaket. Hier nur Skelett-Routes und Skelett-Inertia-Middleware.
- **Einladungs-Flow (`ExternalAccessService::invite()`, Einladungs-UI im CRM-Index, Skeleton-Source-Entity-Anlage, Email-Kollisions-Pfade)** — folgt nach Frontend-Paket
- **Scope-Auflösung für die Mainnav** (`resolveAccessibleScopes()`) — folgt im Frontend-Paket
- **EnsureScopedAccess-Middleware** (Tab-spezifische Scope-Prüfung) — kommt mit dem Tab-Sharing-Paket
- **Staging-Layer / Approval-Workflow** — eigenes Paket
- **Admin-UI für globale Settings** — späteres Paket. Hier nur ENV-basierte Konfig.

## Referenzen

- `concept/security-concept.md` — Abschnitte 2 (Auth), 3 (Datenmodell), 6 (Middleware), 10 (Token-Sicherheit)
- `concept/auth.md` — Bestandsaufnahme
- `concept/crm-einstieg.md` — Feature-Kontext
- `app/Providers/FortifyServiceProvider.php` — Vorlage für Rate-Limiter-Pattern und Mail-Versand
- `artwork/Modules/Invitation/Services/InvitationService.php` — Vorlage für Token-Pattern
- `app/Http/Kernel.php` — Middleware-Group-Pattern
- `app/Providers/AuthServiceProvider.php` — Stelle für `Gate::before()`-Schutz
