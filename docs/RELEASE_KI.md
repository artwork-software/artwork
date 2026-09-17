# Release-Checkliste: KI-Assistent

Gilt für das erste Release, das den KI-Assistenten mitbringt (Module `AiAssistant`, Passport-13-Schema).
Reihenfolge ist verbindlich. Jeder Abschnitt nennt die echten Namen aus diesem Repository.

Kurzfassung für den eiligen Fall: **A** ist Pflicht (Backup, abgelaufene Keys melden, `APP_VERSION`),
**B** läuft im Container-Deploy größtenteils von selbst — außer `ai:install`, das jemand von Hand
aufrufen und dessen Ausgabe er notieren muss. Ohne **D** bleibt der Assistent aus; das ist der
gewollte Auslieferungszustand.

---

## A. Vor dem Deploy, je Instanz

### A1 — Backup der Datenbank

Das Release verändert `oauth_clients` **unwiderruflich**: die Migration
`database/migrations/2026_09_14_100000_upgrade_oauth_clients_to_passport_13.php` hat bewusst kein
`down()`, weil die Abbildung `grant_types` → `personal_access_client`/`password_client` nicht
verlustfrei umkehrbar ist. `database/migrations/2026_09_14_100100_drop_oauth_personal_access_clients_table.php`
löscht zusätzlich `oauth_personal_access_clients`.

**Rollback ist ausschließlich ein Restore.** Vor dem Deploy:

```bash
mysqldump -u <user> -p <datenbank> > artwork-vor-ki-release-$(date +%F).sql
```

### A2 — Abgelaufene Kunden-Keys ermitteln und dem Haus melden

Seit `core-schritt1-rueckfluss` prüft `artwork/Core/Api/Middleware/EnsureTokenNotExpired.php` das
Ablaufdatum wirklich. Keys, die bisher trotz abgelaufenem `expires_at` funktioniert haben, hören
mit diesem Release auf zu funktionieren — die Antwort ist dann `401` mit `{"error":"token_expired"}`.

```sql
select id, name, user_id, expires_at
from oauth_access_tokens
where revoked = 0 and expires_at < now();
```

Ergebnis **vor** dem Deploy an das Haus melden. Leere Ergebnismenge = nichts zu tun.

### A3 — `APP_VERSION` setzen

`APP_VERSION` = Git-Tag ohne führendes `v` (Tag `v3.12.0` → `APP_VERSION=3.12.0`).

Derselbe String ist in artwork-ai der Name des eingefrorenen Contracts und der Korpus-Version.
Ohne ihn liefert `config('app.version')` (`config/app.php:24`) `null`; die Bar schickt dann
`context.artwork_version = null` (`resources/js/Composeables/useAiAssistant.js:79`) und artwork-ai
findet weder Contract noch Korpus.

### A4 — Env prüfen

| Schlüssel | Wert beim Deploy | Warum |
|---|---|---|
| `AI_ENABLED` | `false` | Globaler Kill-Switch des Betreibers. Bleibt aus, bis das Haus onboarded ist (D). |
| `AI_ENDPOINT_URL` | Basis-URL von artwork-ai | Kein Haus-Setting: ein editierbarer Endpunkt hieße, dass jeder mit Einstellungsrechten Session-Tokens auf einen fremden Server schicken lässt (`config/ai.php`). |
| `AI_WIKI_BASE_URL` | `https://wiki.artwork.software/de/docs` | Ziel von `GET /help/{pfad}`. Das Sprachsegment setzt artwork nach der Sprache des Nutzers. |
| `APP_VERSION` | siehe A3 | |

Die Feinjustierungs-Schlüssel (`AI_TEXT_LIMIT`, `AI_DAY_WINDOW_FROM`/`_TO`,
`AI_ACTION_TTL_MINUTES`, `AI_UNDO_HOURS`) stehen in `.env.example` auskommentiert; die Vorgaben in
`config/ai.php` reichen. `AI_DEV_STUB_SCHEMA_PATH` gehört nur in eine `local`-Umgebung.

---

## B. Deploy

### B5 — Migrationen

Im Container-Deploy laufen sie automatisch: `dockerfiles/entrypoint.sh:40` ruft
`php artisan artwork:container-update`, das seinerseits `migrate --force` ausführt
(`artwork/Core/Console/Commands/UpdateContainerCommand.php`). Manuell:

```bash
php artisan migrate --force
```

Es laufen genau diese neun, **in dieser Reihenfolge** (nach Zeitstempel, nicht nach Thema):

| # | Datei | Was |
|---|---|---|
| 1 | `database/settings/2026_09_13_190000_create_ai_assistant_settings.php` | Haus-Settings `ai_assistant.*`: `enabled`, `level_b_enabled`, `level_c_enabled` je `false`, `transcript_retention_days` `0`, `ai_session_client_id` `null` |
| 2 | `database/migrations/2026_09_13_190100_add_ai_assistant_settings_permission.php` | Recht `change ai assistant settings` aus dem Permission-Katalog |
| 3 | `database/migrations/2026_09_14_100000_upgrade_oauth_clients_to_passport_13.php` | `oauth_clients` auf Passport 13 (`owner_type`/`owner_id`, `redirect_uris`, `grant_types`; alte Spalten weg). **Kein `down()`** |
| 4 | `database/migrations/2026_09_14_100100_drop_oauth_personal_access_clients_table.php` | `oauth_personal_access_clients` entfällt. **Kein `down()`** |
| 5 | `database/migrations/2026_09_14_100200_normalize_personal_access_client.php` | Personal-Access-Client auf die Form, die Passport 13 erwartet |
| 6 | `database/migrations/2026_09_14_110000_create_ai_conversations_table.php` | `ai_conversations` |
| 7 | `database/migrations/2026_09_14_110100_create_ai_conversation_turns_table.php` | `ai_conversation_turns` |
| 8 | `database/migrations/2026_09_16_090000_create_ai_action_batches_table.php` | `ai_action_batches` |
| 9 | `database/migrations/2026_09_16_090100_create_ai_action_batch_items_table.php` | `ai_action_batch_items` |

Nr. 1 ist eine Settings-Migration (`database/settings/`, Spatie); sie läuft mit `migrate` mit, weil
`config/settings.php` das Verzeichnis unter `migrations_paths` führt. Nr. 3 ist auf einer frischen
Installation ein No-op (sie steigt aus, wenn `oauth_clients.grant_types` schon existiert).

### B6 — `ai:install`

**Läuft nicht automatisch mit.** Auf jeder Instanz einmal:

```bash
php artisan ai:install
```

Ausgabe notieren — die Client-ID braucht artwork-ai beim Tenant-Onboarding (D12), sie ist dort die
`aud` der Session-Tokens. Der Aufruf ist idempotent: steht die ID schon in den Settings und der
Client existiert, passiert nichts und die ID wird erneut ausgegeben
(`artwork/Modules/AiAssistant/Console/Commands/InstallCommand.php`).

### B7 — Scheduler prüfen

```bash
php artisan schedule:list
```

Diese drei müssen auftauchen (`app/Console/Kernel.php:72,74,78`):

| Command | Takt | Zweck |
|---|---|---|
| `passport:purge` | täglich 02:30 | abgelaufene und widerrufene Tokens |
| `ai:purge-transcripts` | täglich 02:45 | Chat-Historie nach `transcript_retention_days` |
| `ai:expire-actions` | stündlich | offene bestätigungspflichtige Aktionen nach Fristablauf |

Kein Eintrag sichtbar? Dann läuft der Scheduler selbst nicht — `php artisan schedule:run` gehört in
den Cron der Instanz.

`ai:purge-transcripts` ist nach dem Deploy wirkungslos, solange `transcript_retention_days` auf `0`
steht — das ist der Auslieferungswert und heißt „unbegrenzt aufbewahren". Ein Haus, das eine Frist
will, setzt sie auf der Einstellungsseite; erst dann löscht der Job.

### B8 — Caches und Release-Assets

`php artisan optimize` läuft im Container-Deploy mit (`dockerfiles/entrypoint.sh:41`). Manuell:

```bash
php artisan optimize
```

Die Contract-Artefakte hängt die CI an den Release an, sobald ein Tag `v*` gepusht wird —
Job `contract-release-assets` in `.github/workflows/workflow.yml`. Er hängt an `openapi-contract`
(prüft, dass `openapi.json` nicht veraltet ist) und lädt hoch:

- `openapi.json` — committet im Repo
- `routes-web.json` — im Job erzeugt über `php artisan ai:export-routes`; bewusst nicht im Repo
  (`.gitignore:46`), weil es an den Routen hängt und nicht an einem Review

artwork-ai holt beides pro Version als Release-Asset.

---

## C. Nach dem Deploy: Smoke-Test ohne KI

Der Assistent ist an dieser Stelle aus (`AI_ENABLED=false`). Geprüft wird, dass das Release nichts
Bestehendes gebrochen hat.

### C9 — Oberfläche unverändert

Login, Kalender, Dienstplan und die Inventar-Listen durchklicken. Hintergrund: `core-visibility-scopes`
hat die Sichtbarkeitsregeln für Projekt, Termin, Raum, Schicht und Inventar auf gemeinsame
`visibleFor()`-Scopes umgestellt, die auch die Oberfläche benutzt. Die Datenseite deckt die
Testsuite ab; was hier zählt, ist der Blick eines Menschen auf die fertigen Seiten.

### C10 — Kunden-Keys

```bash
# gültiger Key -> 200
curl -s -o /dev/null -w '%{http_code}\n' \
  -H "Authorization: Bearer <gueltiger-key>" -H 'Accept: application/json' \
  https://<instanz>/api/user

# abgelaufener Key -> 401 {"error":"token_expired"}
curl -s -H "Authorization: Bearer <abgelaufener-key>" -H 'Accept: application/json' \
  https://<instanz>/api/user
```

`/api/user` ist hier die richtige Adresse, nicht `/api/v1/...`: unter `/api/v1` liegen ausschließlich
die Endpunkte des Assistenten, die zusätzlich einen `ai:*`-Scope verlangen (`routes/api_v1.php`).
Ein Kunden-Key trägt den nicht. Wer einen Key mit `inventory:read` prüfen will, nimmt
`GET /api/inventory`.

### C11 — JWKS

```bash
curl -s https://<instanz>/api/v1/ai/jwks
```

Erwartet: `200` und ein Objekt `{"keys":[{"kty":"RSA","use":"sig","alg":"RS256","kid":"...","n":"...","e":"..."}]}`.
Der Endpunkt ist bewusst unauthentifiziert — der Schlüssel ist öffentlich, das ist der Zweck eines
Public Key. artwork-ai verifiziert damit die Session-Tokens des Hauses, ohne dass beim Onboarding
ein PEM von Hand gepflegt werden muss.

Fehlschlag hier heißt fast immer: der Passport-Schlüssel fehlt oder ist nicht lesbar
(`php artisan passport:keys`).

---

## D. Onboarding eines Hauses (nur wenn gewünscht)

### D12 — Tenant in artwork-ai anlegen

Im Admin-Panel von artwork-ai: Instanz-URL, Hostname, **Client-ID aus B6**, LiteLLM-Key,
`artwork_version` (= `APP_VERSION` aus A3).

### D13 — Instanz scharf schalten

1. `AI_ENABLED=true` setzen, Deploy/Neustart.
2. In artwork unter Tooleinstellungen → „KI-Assistent" das Haus-Setting `enabled` anschalten
   (Recht `change ai assistant settings`).

Stufe B (Datenzugriff) und Stufe C (Schreiben) bleiben getrennt davon aus. Stufe B setzt eine
Betriebs- bzw. Dienstvereinbarung beim Haus voraus, Stufe C zusätzlich den abgeschlossenen
Auth-Sweep. Beide Flags stehen auf derselben Einstellungsseite.

### D14 — Smoke-Test der Bar

| Eingabe | Erwartung |
|---|---|
| Hilfe-Frage („Wie lege ich eine Terminserie an?") | Antwort mit Quellenangaben `[Quelle n]`, Wiki-Links relativ über `/help/...` |
| Off-Topic („Schreib mir ein Gedicht") | freundliche Abweisung, keine Antwort |
| Datenfrage („Welche Termine habe ich morgen?") bei Stufe B aus | Hinweis, dass Datenzugriff im Haus nicht aktiviert ist; keine Daten. Der Text kommt aus artwork-ai (Prompt-Seed `help.level_b_disabled`), nicht aus artwork. |

---

## E. Rollback

| Stand | Rückweg |
|---|---|
| Nach B5 (Migrationen gelaufen) | **Nur Restore** aus A1, Code zurück auf den vorherigen Tag. Die Migrationen 3 und 4 haben kein `down()`. |
| Nach D13 (Haus scharf) | `AI_ENABLED=false` genügt. Keine Datenänderung nötig; Gespräche und Aktionsprotokolle bleiben erhalten und sind nach dem Wiedereinschalten wieder da. |

Zwischen B5 und D13 gibt es nichts zurückzurollen: der Assistent ist aus, die neuen Tabellen sind
leer, und die Settings stehen auf ihren Defaults.

---

## Anhang: Probelauf auf einer Bestandsinstanz nachstellen

Für Tests ohne Kundendaten lässt sich eine Instanz „vor dem Release" nachbauen: leere Datenbank
anlegen, `migrate` fahren, dann `oauth_clients` per SQL auf Passport 12 zurücksetzen
(`user_id`, `redirect`, `personal_access_client`, `password_client` zurück; `grant_types`,
`redirect_uris`, `owner_*` weg), `oauth_personal_access_clients` wieder anlegen, die `ai_*`-Tabellen
löschen, die `ai_assistant`-Zeilen aus `settings` und das Recht aus `permissions` entfernen — und
zuletzt die neun Migrationen aus B5 aus der Tabelle `migrations` austragen. Danach bildet
`php artisan migrate --force` genau den Weg ab, den eine Kundeninstanz nimmt.
