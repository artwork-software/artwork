# artwork – Projektkonventionen

## Laravel-10-Verzeichnisstruktur beibehalten

- Das Projekt wurde von Laravel 10 hochgezogen, ohne auf die neue, verschlankte Struktur zu migrieren. Das bleibt so, solange es nicht ausdrücklich anders gewünscht ist.
- Middleware liegt in `app/Http/Middleware/` bzw. `artwork/Core/Http/Middleware/`, Service Provider in `app/Providers/`.
- Es gibt keine Anwendungskonfiguration in `bootstrap/app.php`:
    - Middleware-Registrierung: `app/Http/Kernel.php`
    - Exception-Handling: `app/Exceptions/Handler.php`
    - Commands und Schedule: `app/Console/Kernel.php`
    - Rate Limits: `RouteServiceProvider` bzw. `app/Http/Kernel.php`
- Fachlicher Code liegt in Modulen unter `artwork/Modules/*` (Models, Services, Repositories, Http, Policies); Altbestand unter `app/`.

## Lokale Umgebung (DDEV)

- PHP, Artisan und Tests laufen im Container: `ddev exec php artisan …`, Tests mit `ddev exec php artisan test --compact <datei>`. Direkt auf dem Host scheitert die DB-Verbindung (Host `db`).
- Die Test-Datenbank `artwork_test` wird nicht pro Lauf migriert: nach neuen Migrationen `ddev exec "DB_DATABASE=artwork_test php artisan migrate --force"`.
- Nie zwei Testläufe gleichzeitig starten (gemeinsame Test-DB und Storage-Fakes → Schein-Fehlschläge).

## Datenbank

- Beim Ändern einer Spalte müssen alle bisherigen Attribute (nullable, default, …) im Migrationscode wiederholt werden, sonst gehen sie verloren.
