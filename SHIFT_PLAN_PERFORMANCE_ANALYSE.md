# Shift-Plan Performance-Analyse & Refactor-Plan

## Status-Zusammenfassung

> **Alle 3 Stufen sind umgesetzt.** Die Performance-Optimierung des Shift-Plans ist funktional abgeschlossen.

| Stufe | Beschreibung | Status |
|-------|-------------|--------|
| **Stufe 1** | Normalisierte Response-Struktur mit minimalen DTOs | ✅ Umgesetzt |
| **Stufe 2** | Batch-Endpoint + Context-Memoization | ✅ Umgesetzt |
| **Stufe 3** | Berechnete Felder ins Frontend verlagern | ✅ Umgesetzt |

**Verbleibende optionale Punkte:**
- Performance-Messung (Abschnitt 5) wurde nicht ausgefuehrt — quantitative Akzeptanzkriterien (Payload-Groesse, Response-Zeit, Query-Anzahl) sind nicht gemessen
- Typisierte Lookup-DTOs fehlen (Lookups als raw Arrays statt CraftLookupDTO etc.) — funktional kein Problem
- Lookups kommen ueber Batch-Endpoint statt separatem Meta-Endpoint — funktional kein Problem
- SubEvent `formattedDates` wird noch vom Server serialisiert (nur regulaerer Kalender, nicht Shift-Plan)

---

## Executive Summary

**Top-3-Befunde:**

1. **`buildShiftPlanContext()` wird pro Raum redundant ausgeführt** — Bei 24 Räumen entstehen **168+ identische DB-Queries** (User-Settings, Filter, gefilterte Raumliste, Holidays, Projekt-Daten). Jeder `/shift-plan-room`-Call baut den kompletten Kontext neu auf statt ihn zu cachen. → **~75% der Queries sind redundant.**

2. **Volle Model-Serialisierung statt minimaler DTOs** — `ShiftDTO` bettet vollständige Eloquent-Models ein (User mit ~60 Spalten, Craft mit auto-geladenen `craftShiftPlaner`-Usern, Room mit auto-geladenen `admins`). `$appends` auf User/Freelancer/ServiceProvider triggern **teure SVG-Avatar-Generierung bei jeder Serialisierung**. Die Event-Model hat **12 appended Attributes**, die bei jeder Serialisierung berechnet werden.

3. **Massive Datenduplikation** — Dieselben User/Craft/Project-Objekte werden mehrfach pro Response serialisiert. Ein User, der in 5 Shifts arbeitet, wird 5× als volles Objekt serialisiert. Jeder Craft serialisiert zusätzlich alle seine `craftShiftPlaner` (vollständige User-Objekte). Geschätzte Payload-Reduktion durch Normalisierung: **60-80%**.

**Erwartete Verbesserung nach vollständigem Refactor:**
- Response-Zeit pro Raum: 28s → **< 3s**
- Gesamte Page-Load-Zeit: 3,5 min → **< 10s**
- Payload pro Raum: 3-60 KB → **< 5 KB** (+ einmalige Meta-Maps ~50 KB)
- DB-Queries gesamt: 264-336 → **< 50**

---

## 1. Bestandsaufnahme der Endpoints

### 1.1 Route-Definitionen

**Datei:** `routes/web.php:748-749`

```php
Route::get('/response/shift-plan-meta', [EventController::class, 'shiftPlanMetaAPI'])->name('shift.plan.meta');
Route::get('/response/shift-plan-room', [EventController::class, 'shiftPlanRoomAPI'])->name('shift.plan.room');
```

Middleware: `auth:sanctum`, `verified` (GET-Requests)

### 1.2 Controller-Methoden

**Datei:** `app/Http/Controllers/EventController.php`

```php
// Zeile 735-738
public function shiftPlanMetaAPI(Request $request): JsonResponse
{
    return response()->json($this->shiftPlanService->getMeta($request));
}

// Zeile 740-752
public function shiftPlanRoomAPI(Request $request): JsonResponse
{
    if ($request->query('room_id') === null || $request->query('room_id') === '') {
        return response()->json(['error' => 'room_id required'], 422);
    }
    $result = $this->shiftPlanService->getRoomContent($request);
    if ($result === null) {
        return response()->json(['error' => 'Room not found or not in filter'], 404);
    }
    return response()->json($result);
}
```

→ **Keine API Resources**, direkte JSON-Serialisierung von Arrays/DTOs.

### 1.3 Service-Klassen & Call-Chain

```
EventController
  └── ShiftPlanService (artwork/Modules/Calendar/Services/ShiftPlanService.php)
        ├── CalendarDataService (artwork/Modules/Calendar/Services/CalendarDataService.php)
        │     ├── getCalendarDateRange()
        │     ├── getFilteredRooms()
        │     └── createCalendarPeriodDto()
        ├── ShiftCalendarService (artwork/Modules/Calendar/Services/ShiftCalendarService.php)
        │     ├── filterRoomsEventsAndShifts()
        │     └── mapRoomsToContentForCalendar()
        ├── SingleShiftPresetService (artwork/Modules/Shift/Services/SingleShiftPresetService.php)
        │     └── getAllPresets()
        └── ProjectService (artwork/Modules/Project/Services/ProjectService.php)
              └── findById()
```

### 1.4 Eager-Loading pro Endpoint

#### `/shift-plan-meta` — `ShiftPlanService::getMeta()`

| Query | Eager Loading | Datei:Zeile |
|-------|--------------|-------------|
| `Room::query()` | `admins:id,first_name,last_name,profile_photo_path`, `requestableBy:id` | CalendarDataService.php:195-196 |
| `SingleShiftPreset::query()` | `craft:id,name,abbreviation,color`, `shiftsQualifications:id,name,icon,available` | SingleShiftPresetService.php:53-60 |
| `ShiftPresetGroup::query()` | `presets` (mit `craft:id,name,abbreviation,color`, `shiftsQualifications:id,name,icon,available`) | ShiftPlanService.php:169-192 |
| `Holiday::query()` | keine | CalendarDataService.php:107 |

#### `/shift-plan-room` — `ShiftPlanService::getRoomContent()`

| Query | Eager Loading | Datei:Zeile |
|-------|--------------|-------------|
| Alles von Meta (redundant!) | siehe oben | ShiftPlanService.php:50 |
| `Event::query()` | `eventStatus:id,color`, `event_type:id,name,abbreviation,hex_code`, `room:id,name`, `creator:id,first_name,last_name,profile_photo_path`, `eventProperties:id,name,icon` | ShiftCalendarService.php:60-90 |
| `Shift::query()` | `room:id,name`, `craft:id,name,abbreviation`, `craft.qualifications:id,name`, `craft.craftShiftPlaner` (KEINE Spalten-Constraint!), `shiftsQualifications`, `globalQualifications`, `users:id,first_name,last_name,pronouns,position,profile_photo_path`, `users.globalQualifications:id`, `freelancer:id,...`, `freelancer.globalQualifications:id`, `serviceProvider:id,...`, `serviceProvider.globalQualifications:id`, `shiftGroup:id,name` | ShiftCalendarService.php:95-148 |
| `Project::query()` (smart) | `status:id,name,color`, `users:id`, `groups` (mit nested groups) | ShiftCalendarService.php:159-170 |

### 1.5 Caching-Status

**Aktuell: KEIN Caching auf diesen Endpoints.**

`Cache` ist in `CalendarDataService.php` importiert (Zeile 32) aber **nirgends verwendet**. Alle Queries laufen bei jedem Request frisch.

---

## 2. Datenduplikations-Analyse

### 2.1 Vollständige Model-Serialisierung — Was wird alles mitgeschickt?

#### User-Model (in `ShiftDTO.users`, `ShiftDTO.craft.craftShiftPlaner`, etc.)

| Kategorie | Details |
|-----------|---------|
| DB-Spalten | **~65 Spalten** (inkl. `toggle_hints`, `zoom_factor`, `compact_mode`, `drawer_height`, `checklist_*`, `chat_public_key`, `salary_per_hour`, etc.) |
| `$hidden` | nur 4: `password`, `remember_token`, `two_factor_*` |
| `$appends` | 4: `profile_photo_url` (**SVG-Avatar-Generierung!**), `full_name`, `type`, `formated_work_time_balance` |
| Serialisierte Felder pro User | **~60+ Felder** |
| Davon im Frontend genutzt | **5-6 Felder** (id, first_name, last_name, profile_photo_url, pivot.shift_qualification_id) |

⚠️ **`profile_photo_url`-Accessor** generiert bei fehlendem Profilbild ein **vollständiges SVG mit Base64-Encoding** (~300+ Zeichen) — und das bei JEDER Serialisierung!

#### Craft-Model (in `ShiftDTO.craft`)

| Kategorie | Details |
|-----------|---------|
| DB-Spalten | ~10 |
| `$with` (AUTO-LOAD!) | **`craftShiftPlaner`** (BelongsToMany→User), **`craftInventoryPlaner`** (BelongsToMany→User) |
| Problem | Jeder Craft serialisiert ALLE seine Shift-Planer als vollständige User-Objekte! |

⚠️ **Kritisch:** `craft.craftShiftPlaner` wird im Eager-Loading **ohne Spalten-Constraint** geladen (ShiftCalendarService.php:106/121). Das bedeutet: jeder Craft in der Response enthält alle seine Planer mit ~60 Feldern pro User.

#### Room-Model (in `ShiftDTO.room`)

| Kategorie | Details |
|-----------|---------|
| `$with` (AUTO-LOAD!) | **`admins`** (BelongsToMany→User), **`creator`** (BelongsTo→User) |
| Im Shift-Context | Room wird mit `room:id,name` geladen → `$with` wird **nicht** getriggert (Spalten-Constraint überschreibt) |

→ Room ist im Shift-Context sauber (nur id, name). ✅

#### Freelancer-Model (in `ShiftDTO.freelancer`)

| Kategorie | Details |
|-----------|---------|
| `$appends` | 5: `name`, `display_name`, `type`, **`profile_photo_url`** (SVG!), **`assigned_craft_ids`** (Query!) |
| Problem | `assigned_craft_ids` führt eine **zusätzliche DB-Query** bei jeder Serialisierung aus |

#### ServiceProvider-Model (in `ShiftDTO.serviceProviders`)

| Kategorie | Details |
|-----------|---------|
| `$appends` | 4: `name`, `type`, **`profile_photo_url`** (SVG!), **`assigned_craft_ids`** (Query!) |
| Problem | Identisch zu Freelancer |

#### Event-Model ($appends Problem)

| Kategorie | Details |
|-----------|---------|
| `$appends` | **12 Attribute!**: `days_of_event`, `start_time_without_day`, `end_time_without_day`, `event_date_without_time`, `formatted_dates`, `dates_for_series_event`, `times_without_dates`, `start_hour`, `event_length_in_hours`, `hours_to_next_day`, `minutes_form_start_hour_to_start` |
| Im DTO genutzt | nur 2: `days_of_event`, `formatted_dates` |
| Problem | Die anderen 10 werden trotzdem berechnet, weil `$appends` global gilt. Die DTO-Konstruktion nutzt `getAttribute()`, was die Appends triggert. |

⚠️ **Klärung nötig:** Werden die 10 ungenutzten Event-Appends tatsächlich bei der DTO-Konstruktion berechnet? `getAttribute('days_of_event')` greift direkt auf den Accessor zu, aber die JSON-Serialisierung des Event-Objekts (falls irgendwo noch das Model statt DTO zurückgegeben wird) würde alle 12 Appends berechnen. In `EventShiftPlanDTO::fromModel()` werden nur 2 explizit abgerufen, die anderen werden NICHT getriggert — **es sei denn** das Event-Objekt selbst wird woanders serialisiert.

### 2.2 Duplikations-Schätzung (Kampnagel-Szenario)

**Annahmen** (basierend auf "große Datenmenge, viele Räume, viele Events, viele User"):

| Entität | Geschätzte Anzahl unique | Beschreibung |
|---------|--------------------------|-------------|
| Räume | 24 | Parallele Requests |
| Events | ~300 | Über alle Räume im Zeitraum |
| Shifts | ~400 | Über alle Räume im Zeitraum |
| Unique User (in Shifts) | ~80 | Verschiedene Mitarbeiter |
| Unique Freelancer | ~20 | |
| Unique ServiceProvider | ~10 | |
| Unique Crafts | ~12 | |
| Unique Projects | ~25 | |
| Unique EventTypes | ~8 | |
| CraftShiftPlaner pro Craft | ~5 | User die Schichten planen dürfen |

#### Aktuelle Serialisierungen vs. Minimum

| Entität | Aktuelle Serialisierungen (geschätzt) | Minimale (normalisiert) | Faktor | Bytes/Instanz (aktuell) | Verschwendung |
|---------|--------------------------------------|------------------------|--------|------------------------|---------------|
| **User** (in shifts) | ~600 (400 Shifts × ~1.5 User/Shift) | 80 | **7.5×** | ~2.500 B (60 Felder) | ~1.300 KB |
| **User** (in craft.craftShiftPlaner) | ~240 (400 Shifts × 1 Craft × ~5 Planer, aber Craft wird dedupliziert pro Room) | 60 (unique Planer) | **4×** | ~2.500 B | ~450 KB |
| **Freelancer** | ~100 | 20 | **5×** | ~1.500 B | ~120 KB |
| **ServiceProvider** | ~60 | 10 | **6×** | ~1.200 B | ~60 KB |
| **Craft** (mit Planern) | ~400 (1 pro Shift) | 12 | **33×** | ~500 B (ohne Planer) | ~194 KB |
| **Project** (in Shifts) | ~200 | 25 | **8×** | ~800 B | ~140 KB |
| **EventType** | ~200 (in Events) | 8 | **25×** | ~300 B | ~58 KB |
| **ShiftGroup** | ~100 | ~15 | **7×** | ~150 B | ~13 KB |

#### Zusammenfassung

| Metrik | Aktuell (geschätzt) | Nach Normalisierung | Reduktion |
|--------|--------------------|--------------------|-----------|
| **Payload gesamt** (alle 24 Räume) | **~3-4 MB** | **~400-600 KB** | **~80%** |
| **User-Serialisierungen** | ~840 | ~80 | **90%** |
| **Craft-Serialisierungen** | ~400 | ~12 | **97%** |
| **SVG-Avatar-Generierungen** | ~1.000 (User+FL+SP) | ~110 | **89%** |
| **DB-Queries** (24 Räume) | 264-336 | ~30-50 | **85%** |

### 2.3 Felder pro User die mitgeschickt aber nie genutzt werden

**Im Frontend genutzt (ShiftDropElement.vue, ShiftPlan.vue):**
- `id`
- `first_name`
- `last_name`
- `profile_photo_url` (aber nur für Avatare, könnte lazy geladen werden)
- `pivot.shift_qualification_id`
- `type` (zur Unterscheidung User/Freelancer/SP)

**Mitgeschickt aber NICHT genutzt (Auswahl der schlimmsten):**
- `email`, `phone_number`, `position`, `business`, `description`
- `toggle_hints`, `zoom_factor`, `compact_mode`, `is_sidebar_opened`
- `show_crafts`, `at_a_glance`, `notification_enums_last_sent_dates`
- `work_time_balance`, `formated_work_time_balance`
- `checklist_has_projects`, `checklist_no_projects`, `checklist_private_checklists`, etc.
- `chat_public_key`, `use_chat`
- `salary_per_hour`, `salary_description`
- `weekly_working_hours`
- `drawer_height`, `inventory_sort_column_id`, `inventory_sort_direction`
- `bulk_sort_id`, `bulk_column_size`, `show_description_in_bulk`
- **~50 Felder die nie im Shift-Plan-Kontext angezeigt werden**

---

## 3. Frontend-Verwendungs-Analyse

### 3.1 Datenfluss im Frontend

**Datei:** `resources/js/Pages/Shifts/ShiftPlan.vue` (~3.200 Zeilen)

```
1. GET /shift-plan-meta → { days, rooms, singleShiftPresets, shiftGroupPresets }
2. Promise.all(rooms.map(r => GET /shift-plan-room?room_id=r.roomId))
3. roomPayloads → newShiftPlanData (ref)
4. normalizeShiftPlan() → shiftPlanArrayRef (computed)
5. Virtual2DGridWithHeader rendert Zellen
6. Jede Zelle: getRoomDayEvents(room, day), getRoomDayShifts(room, day)
7. ShiftDropElement.vue rendert einzelne Shifts
```

**Kein Pinia Store** — Daten werden als reactive `ref()` gehalten und per `provide/inject` an Kindkomponenten weitergegeben.

### 3.2 Fetch-Orchestrierung

```javascript
// ShiftPlan.vue:1780-1788
const roomPayloads = await Promise.all(
    metaRooms.map((r) =>
        axios.get(route('shift.plan.room'), {
            params: { ...baseParams, room_id: r.roomId },
        }).then((res) => res.data.room),
    ),
)
```

→ **Parallel via Promise.all**, ABER: bei 24 Räumen trifft das HTTP/1.1-Connection-Limit des Browsers (6 gleichzeitige Verbindungen pro Host). Effektiv laufen also 4 "Wellen" à 6 Requests = 4 × 28s ≈ **112s Netzwerk-Zeit** (nicht 24 × 28s sequenziell, aber auch nicht voll parallel).

### 3.3 Tatsächlich genutzte Felder pro Entität

#### EventShiftPlanDTO — genutzte Felder

| Feld | Wo gerendert | Notwendig? |
|------|-------------|-----------|
| `id` | Data-Attribut, Drag&Drop | ✅ |
| `start`, `end` | Zeitanzeige | ✅ |
| `eventName` | Event-Titel | ✅ |
| `project.name`, `project.id`, `project.color`, `project.icon` | Projekt-Header | ✅ |
| `project.status.color` | Status-Indikator | ✅ |
| `eventType.abbreviation`, `eventType.hex_code` | Event-Type-Badge | ✅ |
| `allDay` | Ganztags-Darstellung | ✅ |
| `roomId` | Zuordnung | ✅ |
| `daysOfEvent` | Multi-Tag-Detection | ✅ (aber client-berechenbar) |
| `created_by.first_name`, `.last_name` | Creator-Anzeige | ✅ |
| `isPlanning` | Planungs-Badge | ✅ |
| `occupancy_option`, `option_string` | Options-Indikator | ✅ |
| `is_series` | Serien-Icon | ✅ |
| `formattedDates` | Diverse Darstellungen | ✅ (aber client-berechenbar) |
| `description` | Nicht gerendert im Grid | ⚠️ nur im Detail-Modal |
| `eventProperties` | Nicht im Grid gerendert | ⚠️ nur im Detail |
| `hasTimelines` | Timeline-Indikator | ✅ |

#### ShiftDTO — genutzte Felder

| Feld | Wo gerendert | Notwendig? |
|------|-------------|-----------|
| `id` | Data-Attribut, Drag&Drop | ✅ |
| `start`, `end` | Zeitanzeige (HH:mm) | ✅ |
| `craft.abbreviation` | Craft-Badge | ✅ |
| `craft.id` | Drag-Validierung | ✅ |
| `craft.color` | Craft-Farbe | ✅ |
| `isCommitted` | Lock-Icon | ✅ |
| `inWorkflow` | PR-Icon | ✅ |
| `description` | Shift-Notizen (optional per Setting) | ✅ |
| `shifts_qualifications[].value` | Worker-Count-Berechnung | ✅ |
| `users.length` | Worker-Count | ✅ |
| `freelancer.length` | Worker-Count | ✅ |
| `serviceProviders.length` | Worker-Count | ✅ |
| `users[].pivot.shift_qualification_id` | Qualifikations-Zuordnung | ✅ |
| `daysOfShift` | Multi-Tag-Detection | ✅ (client-berechenbar) |
| `shiftGroupId`, `shiftGroup.name` | Gruppen-Tag | ✅ |
| `break_minutes` | Nicht im Grid | ⚠️ nur im Edit-Modal |
| `eventId` | Zuordnung | ✅ |
| `roomId` | Zuordnung | ✅ |
| `project` | Nicht direkt in Shift-Zelle gerendert | ⚠️ |
| `room` (als Objekt) | Redundant — roomId reicht | ❌ |
| `formatted_dates` | Client-berechenbar | ❌ überflüssig |
| `startOfShift` | Client-berechenbar | ❌ überflüssig |
| `globalQualifications` | Qualifikations-Badge | ✅ |

#### Felder in ShiftDTO die DEFINITIV überflüssig sind:

1. **`room`** (als volles Objekt) — `roomId` ist bereits vorhanden, Room-Name kommt aus `CalendarRoomDTO`
2. **`formatted_dates`** — 4 verschiedene Datums-Formate, trivial client-seitig berechenbar
3. **`startOfShift`** — formatiertes Datum, trivial client-seitig
4. **`daysOfShift`** — Array von Tagen, berechenbar aus `startDate`/`endDate`
5. **`project`** (als volles Model) — Project-Daten sind bereits im Event verfügbar
6. **`craft.craftShiftPlaner`** — Planer-User werden im Shift-Grid NICHT angezeigt
7. **`craft.craftInventoryPlaner`** — wird NICHT im Shift-Plan angezeigt
8. **`craft.qualifications`** — wird separat über `shifts_qualifications` geladen

### 3.4 Normalisierte Struktur — Machbarkeit im Frontend

Die aktuelle `CalendarRoomDTO`-Struktur ist **bereits teilweise normalisiert** (`eventsById`, `shiftsById` als Lookup-Maps). Eine Erweiterung zu voll normalisierten Maps ist mit **moderatem Aufwand** machbar:

**Aktuell:**
```javascript
room.shiftsById[5].craft   // → volles Craft-Objekt
room.shiftsById[5].users   // → Array voller User-Objekte
```

**Ziel:**
```javascript
// Einmalig geliefert (in Meta oder separatem Endpoint):
meta.craftsById[3]          // → { id, name, abbreviation, color }
meta.usersById[7]           // → { id, first_name, last_name, profile_photo_url }

// Pro Raum nur IDs:
room.shiftsById[5].craftId  // → 3
room.shiftsById[5].userIds  // → [7, 12, 15]
```

**Frontend-Aufwand:** Die Stellen, die `shift.craft.abbreviation` lesen, müssten auf `craftsById[shift.craftId].abbreviation` umgestellt werden. Das betrifft primär:
- `ShiftDropElement.vue` (Haupt-Shift-Rendering)
- `ShiftPlan.vue` (Computed Properties)
- Drag&Drop-Logik

Geschätzt **~30-40 Stellen** im Code, aber pattern-basiert ersetzbar.

### 3.5 Date-Library im Frontend

**dayjs** ist verfügbar und wird bereits genutzt:
```javascript
import dayjs from 'dayjs'
import duration from 'dayjs/plugin/duration'
```

Alle server-seitigen Datums-Berechnungen (Wochentag, Weekend, Kalenderwoche, Formatierung) sind damit trivial client-seitig umsetzbar.

---

## 4. Refactor-Plan

### Stufe 1 — Normalisierte Response-Struktur mit minimalen DTOs

**Ziel:** Payload-Reduktion um 80%, SVG-Generierung eliminieren, Duplikation auflösen.

#### 4.1 Neue Response-Struktur

**`/shift-plan-meta` — erweitert um globale Lookup-Maps:**

```json
{
  "days": [CalendarPeriodDTO],
  "rooms": [{ "roomId": 1, "roomName": "Room A" }],
  "singleShiftPresets": [...],
  "shiftGroupPresets": [...],
  "lookups": {
    "craftsById": {
      "3": { "id": 3, "name": "Audio", "abbreviation": "AUD", "color": "#ff0000" }
    },
    "eventTypesById": {
      "1": { "id": 1, "name": "Concert", "abbreviation": "C", "hex_code": "#fff" }
    },
    "projectsById": {
      "5": { "id": 5, "name": "Festival X", "color": "#00ff00", "icon": "music",
             "statusName": "In Progress", "statusColor": "#yellow",
             "isGroup": false, "artistNames": "Band A" }
    },
    "shiftGroupsById": {
      "1": { "id": 1, "name": "Morning Crew" }
    }
  }
}
```

**`/shift-plan-room` — nur IDs statt eingebetteter Objekte:**

```json
{
  "room": {
    "roomId": 1,
    "roomName": "Room A",
    "content": {
      "13.05.2026": { "eventIds": [1, 2], "shiftIds": [5, 6] }
    },
    "eventsById": {
      "1": {
        "id": 1,
        "start": "2026-05-13 10:00",
        "end": "2026-05-13 12:00",
        "eventName": "Konzert",
        "allDay": false,
        "roomId": 1,
        "projectId": 5,
        "eventTypeId": 1,
        "createdBy": { "id": 7, "firstName": "Max", "lastName": "Muster", "profilePhotoUrl": "/path.jpg" },
        "isSeries": false,
        "isPlanning": false,
        "occupancyOption": true,
        "optionString": "Option",
        "hasTimelines": false,
        "eventPropertyIds": [1, 3]
      }
    },
    "shiftsById": {
      "5": {
        "id": 5,
        "start": "14:00",
        "end": "18:00",
        "startDate": "2026-05-13",
        "endDate": "2026-05-13",
        "breakMinutes": 30,
        "eventId": 1,
        "craftId": 3,
        "roomId": 1,
        "shiftGroupId": 1,
        "isCommitted": false,
        "inWorkflow": false,
        "description": null,
        "shiftsQualifications": [
          { "id": 1, "name": "Tontechnik", "value": 2, "workerIds": [7, 12] }
        ],
        "workers": [
          { "id": 7, "type": "user", "firstName": "Max", "lastName": "Muster", "profilePhotoUrl": "/path.jpg", "shiftQualificationId": 1 },
          { "id": 12, "type": "freelancer", "firstName": "Lisa", "lastName": "Klein", "profilePhotoUrl": null, "shiftQualificationId": 1 }
        ],
        "globalQualifications": [{ "id": 1, "name": "Ersthelfer", "quantity": 1 }]
      }
    }
  }
}
```

**Kernänderungen:**
- User/Freelancer/SP werden zu einheitlichen `workers` mit `type`-Feld → eine Liste statt drei
- Craft wird zu `craftId` → Lookup in `meta.lookups.craftsById`
- Project wird zu `projectId` → Lookup in `meta.lookups.projectsById`
- EventType wird zu `eventTypeId` → Lookup in `meta.lookups.eventTypesById`
- Room-Objekt entfällt (roomId reicht, roomName kommt aus CalendarRoomDTO)
- `daysOfEvent`/`daysOfShift`/`formattedDates`/`startOfShift` entfallen (client-berechenbar)
- Workers werden minimal: nur id, type, firstName, lastName, profilePhotoUrl, shiftQualificationId

#### 4.2 Neue/Geänderte Dateien

| Datei | Aktion | Beschreibung |
|-------|--------|-------------|
| `artwork/Modules/Calendar/DTO/ShiftPlanMetaDTO.php` | **NEU** | Wrapper für Meta-Response mit Lookups |
| `artwork/Modules/Calendar/DTO/ShiftPlanLookupsDTO.php` | **NEU** | craftsById, eventTypesById, projectsById, shiftGroupsById |
| `artwork/Modules/Calendar/DTO/CraftLookupDTO.php` | **NEU** | id, name, abbreviation, color |
| `artwork/Modules/Calendar/DTO/EventTypeLookupDTO.php` | **NEU** | id, name, abbreviation, hex_code |
| `artwork/Modules/Calendar/DTO/ProjectLookupDTO.php` | **NEU** | id, name, color, icon, statusName, statusColor, isGroup, artistNames |
| `artwork/Modules/Calendar/DTO/ShiftMinimalDTO.php` | **NEU** | Ersetzt ShiftDTO — nur benötigte Felder, IDs statt Objekte |
| `artwork/Modules/Calendar/DTO/EventMinimalDTO.php` | **NEU** | Ersetzt EventShiftPlanDTO — nur benötigte Felder, IDs statt Objekte |
| `artwork/Modules/Calendar/DTO/ShiftWorkerDTO.php` | **NEU** | Einheitlicher Worker (User/FL/SP) mit type-Feld |
| `artwork/Modules/Calendar/Services/ShiftPlanService.php` | **ÄNDERN** | Lookups bauen in getMeta(), minimale DTOs in getRoomContent() |
| `artwork/Modules/Calendar/Services/ShiftCalendarService.php` | **ÄNDERN** | mapRoomsToContentForCalendar() nutzt neue DTOs |
| `resources/js/Pages/Shifts/ShiftPlan.vue` | **ÄNDERN** | Lookups speichern, Zugriff auf `craftsById[shift.craftId]` etc. |
| `resources/js/Pages/Shifts/Components/ShiftDropElement.vue` | **ÄNDERN** | Lookup-basierter Zugriff statt eingebettete Objekte |
| `resources/js/Pages/Shifts/ShiftPlanDailyView.vue` | **ÄNDERN** | Analog zu ShiftPlan.vue |

#### 4.3 Aufwandsschätzung

| Aufgabe | Stunden |
|---------|---------|
| Neue DTOs erstellen (7 Klassen) | 4-6h |
| ShiftPlanService refactoren (Lookups bauen, neue DTOs nutzen) | 6-8h |
| ShiftCalendarService anpassen | 4-6h |
| Frontend ShiftPlan.vue + ShiftPlanDailyView.vue | 8-12h |
| Frontend ShiftDropElement.vue + Sub-Komponenten | 4-6h |
| Frontend: date-utility für daysOfEvent/daysOfShift | 2h |
| Testing & Debugging | 6-8h |
| **Gesamt** | **34-48h (5-6 Arbeitstage)** |

#### 4.4 Akzeptanzkriterien

- [ ] Response für einen Raum (Kampnagel-Datenmenge, ~15 Shifts, ~20 Events): **< 5 KB** *(nicht gemessen)*
- [ ] Meta-Response (inkl. Lookups): **< 80 KB** *(nicht gemessen)*
- [ ] Gesamte Payload für 24 Räume: **< 200 KB** (aktuell ~3-4 MB) *(nicht gemessen)*
- [x] Kein User-Objekt wird mehr als 1× pro Response serialisiert *(via Lookup-Maps)*
- [x] Keine SVG-Avatar-Generierung während Serialisierung *(Worker minimal serialisiert)*
- [x] Frontend zeigt alle bisherigen Informationen korrekt an
- [x] Shift-Drag&Drop funktioniert weiterhin

---

### Stufe 2 — Batch-Endpoint statt Pro-Raum-Calls + Context-Memoization

**Ziel:** 24 HTTP-Requests → 1, redundante Queries eliminieren.

#### 4.5 Neue Route + Controller

```php
// routes/web.php
Route::get('/response/shift-plan-rooms', [EventController::class, 'shiftPlanRoomsBatchAPI'])
    ->name('shift.plan.rooms.batch');
```

```php
// EventController.php
public function shiftPlanRoomsBatchAPI(Request $request): JsonResponse
{
    return response()->json($this->shiftPlanService->getAllRoomsContent($request));
}
```

#### 4.6 Service-Methode

```php
// ShiftPlanService.php
public function getAllRoomsContent(Request $request): array
{
    $shiftPlanContext = $this->buildShiftPlanContext($request); // 1× statt 24×!
    $filteredRooms = $shiftPlanContext['filteredRooms'];

    $useDailyView = /* ... */;

    // EINE Query für ALLE Räume statt 24 separate
    $this->shiftCalendarService->filterRoomsEventsAndShifts(
        $filteredRooms,
        $shiftPlanContext['userCalendarFilter'],
        $shiftPlanContext['calendarStartDate'],
        $shiftPlanContext['calendarEndDate'],
        $useDailyView,
        $shiftPlanContext['currentProject']
    );

    $roomsCalendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
        $filteredRooms,
        $shiftPlanContext['calendarStartDate'],
        $shiftPlanContext['calendarEndDate'],
    );

    return ['rooms' => $roomsCalendarData->rooms];
}
```

#### 4.7 SQL-Query-Struktur

```sql
-- EINE Event-Query für alle Räume statt 24 separate:
SELECT events.id, events.eventName, events.start_time, events.end_time, ...
FROM events
WHERE events.room_id IN (1, 2, 3, ..., 24)   -- statt WHERE room_id = ?
  AND events.start_time <= ?
  AND events.end_time >= ?
  AND events.deleted_at IS NULL;

-- EINE Shift-Query für alle Räume:
SELECT shifts.id, shifts.start, shifts.end, shifts.craft_id, ...
FROM shifts
WHERE shifts.room_id IN (1, 2, 3, ..., 24)
  AND shifts.start_date <= ?
  AND shifts.end_date >= ?;
```

→ `filterRoomsEventsAndShifts()` nutzt bereits `whereIn('room_id', $roomIds)` — es muss nur mit allen Räumen statt einem aufgerufen werden.

#### 4.8 Query-Reduktion

| Metrik | Aktuell (24 Calls) | Batch (1 Call) | Ersparnis |
|--------|-------------------|---------------|-----------|
| buildShiftPlanContext() | 24× (~8 Queries) = 192 | 1× (~8 Queries) = 8 | **184 Queries** |
| Event-Query | 24× = 24 | 1× = 1 | **23 Queries** |
| Shift-Query | 24× = 24 | 1× = 1 | **23 Queries** |
| Eager-Load-Queries | 24× ~4 = 96 | 1× ~4 = 4 | **92 Queries** |
| Project-Query | 24× = 24 | 1× = 1 | **23 Queries** |
| **Gesamt** | **~336** | **~15** | **~95%** |

#### 4.9 Backward-Compatibility

**Empfehlung:** Alten Endpoint für 2 Releases behalten, aber Frontend sofort auf Batch umstellen.

```javascript
// ShiftPlan.vue — NEU (1 Call statt 24)
const response = await axios.get(route('shift.plan.rooms.batch'), { params: baseParams });
const roomPayloads = response.data.rooms;
```

Der alte `/shift-plan-room`-Endpoint bleibt funktionsfähig (z.B. für WebSocket-gesteuerte Einzel-Raum-Refreshes).

#### 4.10 Sofort-Maßnahme: Context-Memoization

Auch OHNE Batch-Endpoint sofort umsetzbar — reduziert die redundanten Queries:

```php
// ShiftPlanService.php
private ?array $cachedContext = null;
private ?string $contextKey = null;         

private function getOrBuildShiftPlanContext(Request $request): array
{
    $key = md5(json_encode([
        $request->query('projectId'),
        $request->query('start_date'),
        $request->query('end_date'),
        $request->user()->id,
    ]));

    if ($this->contextKey === $key && $this->cachedContext !== null) {
        return $this->cachedContext;
    }

    $this->cachedContext = $this->buildShiftPlanContext($request);
    $this->contextKey = $key;
    return $this->cachedContext;
}
```

⚠️ **Aber:** Da jeder HTTP-Request eine neue Service-Instanz erzeugt (Laravel DI = Singleton pro Request), wirkt diese Memoization **nur innerhalb eines Requests** — also nur beim Batch-Endpoint. Für die parallelen Einzel-Requests bringt es nichts. → **Batch-Endpoint ist die eigentliche Lösung.**

#### 4.11 Aufwandsschätzung

| Aufgabe | Stunden |
|---------|---------|
| Batch-Endpoint (Route + Controller + Service) | 3-4h |
| Frontend: Promise.all → single fetch | 2h |
| Testing mit Kampnagel-Datenmenge | 3-4h |
| Alte Endpoints deprecation-markieren | 1h |
| **Gesamt** | **9-11h (1.5 Arbeitstage)** |

#### 4.12 Akzeptanzkriterien

- [x] Nur 1 HTTP-Request für alle Räume *(Batch-Endpoint `shift.plan.rooms.batch`)*
- [ ] Server-Zeit für alle 24 Räume zusammen: **< 5s** (aktuell 24 × 28s) *(nicht gemessen)*
- [ ] DB-Queries: **< 20** (aktuell ~336) *(nicht gemessen)*
- [x] `buildShiftPlanContext()` wird genau 1× aufgerufen *(im Batch nur 1 Aufruf)*
- [x] WebSocket-Refresh für einzelne Räume nutzt weiterhin `/shift-plan-room`

---

### Stufe 3 — Berechnete Felder ins Frontend verlagern

**Ziel:** Server-Last weiter reduzieren, Response schlank halten.

#### 4.13 Felder die entfallen können

| Feld | Aktuell berechnet in | Frontend-Ersatz | Aufwand |
|------|---------------------|-----------------|---------|
| `daysOfEvent` | Event-Model Accessor (`getDaysOfEventAttribute`) | `dayjs` DateRange aus `start`/`end` | 30 min |
| `daysOfShift` | Shift-Model Accessor (`getDaysOfShiftAttribute`) | `dayjs` DateRange aus `startDate`/`endDate` | 30 min |
| `formattedDates` (Event) | Event-Model Accessor (12 Format-Varianten!) | `dayjs.format()` on-demand | 1h |
| `formatted_dates` (Shift) | Shift-Model Accessor (4 Format-Varianten) | `dayjs.format()` on-demand | 30 min |
| `startOfShift` | ShiftDTO::fromModel() Carbon-Formatting | `dayjs(startDate).format('DD.MM.YYYY')` | 15 min |
| `CalendarPeriodDTO.isWeekend` | CalendarDataService | `dayjs(date).day() === 0 \|\| dayjs(date).day() === 6` | 15 min |
| `CalendarPeriodDTO.isMonday` | CalendarDataService | `dayjs(date).day() === 1` | 10 min |
| `CalendarPeriodDTO.isSunday` | CalendarDataService | `dayjs(date).day() === 0` | 10 min |
| `CalendarPeriodDTO.weekNumber` | CalendarDataService | `dayjs(date).isoWeek()` (Plugin `isoWeek`) | 15 min |
| `CalendarPeriodDTO.dayString` | CalendarDataService | `dayjs(date).format('dd')` mit Locale | 15 min |
| `CalendarPeriodDTO.monthNumber` | CalendarDataService | `dayjs(date).month()` | 10 min |
| `CalendarPeriodDTO.isFirstDayOfMonth` | CalendarDataService | `dayjs(date).date() === 1` | 10 min |
| `CalendarPeriodDTO.addWeekSeparator` | CalendarDataService | `dayjs(date).day() === 0` | 10 min |
| `CalendarPeriodDTO.fullDay/shortDay/day/fullDayDisplay` | CalendarDataService | `dayjs` Formatierung | 30 min |

#### 4.14 CalendarPeriodDTO — Verschlankung

**Aktuell (15 Felder):**
```php
day, dayString, isWeekend, fullDay, shortDay, withoutFormat,
fullDayDisplay, weekNumber, isMonday, monthNumber, isSunday,
isFirstDayOfMonth, addWeekSeparator, holidays, hoursOfDay, isExtraRow
```

**Reduziert auf (3 Felder):**
```php
date          // "2026-05-13" (ISO-Format, alles andere client-berechenbar)
holidays      // [{ name, color }] (braucht DB-Query, bleibt server-seitig)
hoursOfDay    // [0..23] oder null (nur bei Daily-View)
```

→ Reduktion: 15 Felder → 3 Felder pro Tag. Bei 30 Tagen × 12 Felder weniger = **360 Felder weniger** pro Meta-Response.

#### 4.15 Frontend-Utility

```typescript
// resources/js/utils/calendarDateUtils.ts
import dayjs from 'dayjs'
import isoWeek from 'dayjs/plugin/isoWeek'
import localeData from 'dayjs/plugin/localeData'
dayjs.extend(isoWeek)

export function getDateInfo(dateStr: string) {
    const d = dayjs(dateStr)
    return {
        day: d.format('DD.MM.'),
        dayString: d.format('dd'),        // Mo, Di, Mi...
        isWeekend: d.day() === 0 || d.day() === 6,
        fullDay: d.format('DD.MM.YYYY'),
        shortDay: d.format('DD.MM'),
        withoutFormat: d.format('YYYY-MM-DD'),
        fullDayDisplay: d.format('DD.MM.YY'),
        weekNumber: d.isoWeek(),
        isMonday: d.day() === 1,
        monthNumber: d.month() + 1,
        isSunday: d.day() === 0,
        isFirstDayOfMonth: d.date() === 1,
        addWeekSeparator: d.day() === 0,
    }
}

export function getDaysInRange(start: string, end: string): string[] {
    const days: string[] = []
    let current = dayjs(start)
    const endDate = dayjs(end)
    while (current.isSameOrBefore(endDate, 'day')) {
        days.push(current.format('DD.MM.YYYY'))
        current = current.add(1, 'day')
    }
    return days
}
```

#### 4.16 Aufwandsschätzung

| Aufgabe | Stunden |
|---------|---------|
| `calendarDateUtils.ts` erstellen | 2h |
| CalendarPeriodDTO verschlanken (Backend) | 1h |
| ShiftDTO/EventDTO: berechnete Felder entfernen | 1h |
| Frontend: alle Zugriffe auf entfernte Felder ersetzen | 3-4h |
| dayjs isoWeek Plugin einbinden + testen | 1h |
| **Gesamt** | **8-10h (1-1.5 Arbeitstage)** |

#### 4.17 Akzeptanzkriterien

- [x] CalendarPeriodDTO liefert nur noch `date`, `holidays`, `hoursOfDay` *(+ `isExtraRow`)*
- [x] `daysOfEvent`/`daysOfShift`/`formattedDates` sind nicht mehr in der Response
- [x] Frontend-Rendering ist identisch (visuell kein Unterschied)
- [x] dayjs isoWeek-Plugin liefert korrekte KW-Nummern (ISO 8601)

---

## 5. Messplan

### 5.1 Baseline-Messung (VOR Refactor)

**Sofort durchführbar:**

```bash
# 1. Laravel Telescope aktivieren (falls nicht aktiv) für Query-Counting
ddev artisan telescope:install
ddev artisan migrate

# 2. Response-Größe messen (curl mit gzip)
curl -s -w "\n%{size_download} bytes, %{time_total}s" \
  -H "Authorization: Bearer TOKEN" \
  "https://APP_URL/response/shift-plan-meta?start_date=2026-05-01&end_date=2026-05-31" \
  -o /dev/null

# 3. Pro Raum messen
for room_id in 1 2 3 ... 24; do
  curl -s -w "Room $room_id: %{size_download} bytes, %{time_total}s\n" \
    -H "Authorization: Bearer TOKEN" \
    "https://APP_URL/response/shift-plan-room?room_id=$room_id&start_date=2026-05-01&end_date=2026-05-31" \
    -o /dev/null
done
```

**Telescope-Metriken festhalten:**
- Anzahl DB-Queries pro Request
- Langsamste Queries (> 100ms)
- Memory-Peak pro Request
- Request-Dauer (Server-Zeit)

### 5.2 Artisan-Command für automatisierte Messung

```php
// app/Console/Commands/MeasureShiftPlanPerformance.php
class MeasureShiftPlanPerformance extends Command
{
    protected $signature = 'measure:shift-plan {user_id} {--rooms=24}';

    public function handle(): void
    {
        $user = User::findOrFail($this->argument('user_id'));
        $this->actingAs($user);

        // Measure Meta
        $start = microtime(true);
        DB::enableQueryLog();
        $meta = app(ShiftPlanService::class)->getMeta($this->buildRequest());
        $metaTime = microtime(true) - $start;
        $metaQueries = count(DB::getQueryLog());
        $metaPayload = strlen(json_encode($meta));
        DB::flushQueryLog();

        $this->info("META: {$metaTime}s, {$metaQueries} queries, {$metaPayload} bytes");

        // Measure per room
        $totalTime = 0;
        $totalQueries = 0;
        $totalPayload = 0;

        foreach ($meta['rooms'] as $room) {
            $start = microtime(true);
            DB::enableQueryLog();
            $result = app(ShiftPlanService::class)->getRoomContent(
                $this->buildRequest(['room_id' => $room['roomId']])
            );
            $roomTime = microtime(true) - $start;
            $roomQueries = count(DB::getQueryLog());
            $roomPayload = strlen(json_encode($result));
            DB::flushQueryLog();

            $totalTime += $roomTime;
            $totalQueries += $roomQueries;
            $totalPayload += $roomPayload;

            $this->line("Room {$room['roomId']}: {$roomTime}s, {$roomQueries} queries, {$roomPayload} bytes");
        }

        $this->info("TOTAL: {$totalTime}s, {$totalQueries} queries, {$totalPayload} bytes");
    }
}
```

### 5.3 Messpunkte nach jeder Stufe

#### Nach Stufe 1 (Normalisierte DTOs)

| Metrik | Baseline | Ziel |
|--------|----------|------|
| Payload pro Raum | 3-60 KB | **< 5 KB** |
| Payload Meta | 125 KB | **< 100 KB** (+ Lookups) |
| Payload gesamt (24 Räume) | ~3-4 MB | **< 300 KB** |
| SVG-Avatar-Generierungen | ~1.000 | **0** |
| Server-Zeit pro Raum | 28s | **< 10s** (Queries noch redundant) |

#### Nach Stufe 2 (Batch-Endpoint)

| Metrik | Nach Stufe 1 | Ziel |
|--------|-------------|------|
| HTTP-Requests | 24+1 | **2** (Meta + Batch) |
| DB-Queries gesamt | ~336 | **< 20** |
| Server-Zeit gesamt | ~280s (24×~10s) | **< 5s** |
| `buildShiftPlanContext()` Aufrufe | 24 | **1** |
| Total Page-Load | ~60s | **< 8s** |

#### Nach Stufe 3 (Client-Berechnung)

| Metrik | Nach Stufe 2 | Ziel |
|--------|-------------|------|
| CalendarPeriodDTO Felder | 15 | **3** |
| Payload Meta | ~100 KB | **< 50 KB** |
| Server-seitige Carbon-Operationen | ~2.000 | **~100** |
| Total Page-Load | ~8s | **< 6s** |

### 5.4 Monitoring-Setup

**Laravel Pulse** (bereits als Dependency vorhanden):
- Request-Duration-Tracking aktivieren
- Slow-Query-Tracking (> 50ms)
- Cache-Hit/Miss-Ratio (falls später Cache eingeführt)

**Sentry Performance** (falls vorhanden):
```php
// In ShiftPlanService
$span = \Sentry\startSpan(['op' => 'shift-plan.get-all-rooms']);
// ... code ...
$span->finish();
```

**Browser DevTools:**
- Network-Tab: Gesamte Transfer-Größe messen (mit gzip)
- Performance-Tab: Total Blocking Time, Largest Contentful Paint
- Vue DevTools: Component Render-Time

### 5.5 Test-Szenario für Kampnagel-Simulation

```php
// database/seeders/KampnagelSimulationSeeder.php
class KampnagelSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 24 Räume
        $rooms = Room::factory(24)->create(['relevant_for_disposition' => true]);

        // 80 User mit can_work_shifts
        $users = User::factory(80)->create(['can_work_shifts' => true]);

        // 12 Crafts mit je 5 Planern
        $crafts = Craft::factory(12)->create();
        foreach ($crafts as $craft) {
            $craft->craftShiftPlaner()->attach($users->random(5)->pluck('id'));
        }

        // 25 Projekte
        $projects = Project::factory(25)->create();

        // 300 Events verteilt auf Räume (30 Tage)
        foreach ($rooms as $room) {
            Event::factory(rand(8, 18))->create([
                'room_id' => $room->id,
                'project_id' => $projects->random()->id,
            ]);
        }

        // 400 Shifts verteilt auf Räume
        foreach ($rooms as $room) {
            $shifts = Shift::factory(rand(12, 22))->create([
                'room_id' => $room->id,
                'craft_id' => $crafts->random()->id,
            ]);
            foreach ($shifts as $shift) {
                // 1-4 Worker pro Shift
                $shift->users()->attach(
                    $users->random(rand(1, 4))->pluck('id'),
                    ['shift_qualification_id' => 1]
                );
            }
        }
    }
}
```

---

## 6. Empfohlene Reihenfolge

| Prio | Stufe | Impact | Aufwand | ROI |
|------|-------|--------|---------|-----|
| **1** | **Stufe 2** (Batch + Context-Memoization) | Queries: 336 → 15, Server-Zeit: 280s → ~30s | 1.5 Tage | **Höchster ROI** |
| **2** | **Stufe 1** (Normalisierte DTOs) | Payload: 3-4 MB → 300 KB, keine SVG-Gen | 5-6 Tage | Höchster absoluter Impact |
| **3** | **Stufe 3** (Client-Berechnung) | Meta-Payload: -50%, saubere Architektur | 1-1.5 Tage | Nice-to-have |

**Empfehlung: Stufe 2 zuerst** — ist der schnellste Win mit geringstem Risiko. Dann Stufe 1 für die Payload-Reduktion. Stufe 3 ist optional und kann auch parallel zu Stufe 1 laufen.

**Gesamtaufwand: ~8-9 Arbeitstage** für alle drei Stufen.

---

## Anhang: Offene Fragen

1. ⚠️ **Klärung nötig:** Wird `ShiftPlanService` als Singleton oder Transient im Container registriert? Wenn Singleton, würde die Context-Memoization auch ohne Batch-Endpoint bei sequentiellen Requests helfen — aber nur wenn der gleiche Prozess die Requests handelt (was bei PHP-FPM standardmäßig nicht der Fall ist).

2. ⚠️ **Klärung nötig:** Gibt es WebSocket-Events, die einzelne Räume aktualisieren? Falls ja, sollte der alte `/shift-plan-room`-Endpoint für granulare Updates erhalten bleiben, während der initiale Load über den Batch-Endpoint läuft.

3. ⚠️ **Klärung nötig:** Die `ShiftPlanDailyView.vue` und `ShiftPlanListView.vue` nutzen vermutlich ähnliche Endpoints — müssen die ebenfalls refactored werden?

4. ⚠️ **Klärung nötig:** Wie viele User sind typischerweise als `craftShiftPlaner` pro Craft eingetragen bei Kampnagel? Dies beeinflusst die Payload des Craft-Lookups. Falls > 20, sollten die Planer-IDs in einen separaten Lookup.
