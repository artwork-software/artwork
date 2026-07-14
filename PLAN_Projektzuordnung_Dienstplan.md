# Umsetzungsplan – Projektzuordnung im Dienstplan (+ Wunscheintragungen)

> Quelle: Kundenanforderung „Produktionen im Dienstplan zuordnen" (Kampnagel/HAU/Hellerau).
> **Sprachregelung: durchgängig „Projekt", nie „Produktion" – auch im Code (Tabellen, Klassen, Routen, UI-Texte).**
> Stand: 13.07.2026 · Caldero Systems GmbH · ersetzt die Fassung vom 08.06.2026
> Bezug: ergänzt Pflichtenheft-Bereich **4.6 Dienstplanung, Schichtplanung und Personal**

> **Umsetzungsstand 13.07.2026: alle 5 Phasen implementiert** (Backend-Kern, Dienstplan-Frontend,
> ShiftTab + Einsatzplan, Verfügbarkeitskalender, Verschiebung/PDF/Übersetzungen).
> 21 neue Feature-Tests grün (`tests/Feature/Modules/Project/ProjectDayAssignmentTest.php`).
> Zeitraum-Synchronisation läuft zentral über den `ProjectDayAssignmentEventObserver`
> (Termin-Anlage, -Verschiebung, -Projektwechsel, -Löschung und -Restore werden alle abgedeckt).
> Browser-Testlauf 13.07.2026 auf Dev-Instanz (Projekt „PZ-Testprojekt Zuordnung"): Zellen-Modal,
> geteiltes Zuordnungs-Modal (Einzeltag + Ganz-Zeitraum), Zellen-Streifen, ShiftTab-Overview +
> Tagesbalken-Avatare, Einsatzplan-Wunsch, Kalender-Wunsch-Streifen, Confirm-Dialog bei
> Verschiebung, Frei-Auflösung (+ Notification + Schichtverlauf) und Wunsch-Akzeptieren verifiziert.
> Hinweise: Wunsch-Buttons nur im User-Einsatzplan (Freelancer/Dienstleister haben keinen Login);
> per Frei-Eintrag aufgelöste Einzeltage einer Ganz-Zeitraum-Gruppe werden bei einer SPÄTEREN
> Zeitraum-Änderung wieder aufgefüllt (Re-Materialisierung kennt den Auflösungsgrund nicht).

---

## 1. Ziel

Kolleg*innen können **ganzen Projekten** im Dienstplan zugeordnet werden – als eigene Ebene
neben den konkreten Schichten. Drei Fälle:

1. **Verbindliche Zuordnung ohne Schicht** (z. B. Technische Leitung): Person ist für den
   Projektzeitraum verbindlich eingeplant, ohne konkrete Schicht.
2. **Vorläufige Zuordnung**: Person wird erst dem Projekt zugeordnet, später konkretisiert
   durch (a) Zuweisung zu einer Schicht desselben Projekts („verwandelt" sich) oder
   (b) Auflösung durch einen „Frei"-Eintrag am Tag.
3. **Wunscheintragungen**: Mitarbeiter*innen tragen sich selbst als Wunsch in Projekte ein –
   sichtbar im Dienstplan, im Projekt-Schichten-Tab und im Verfügbarkeitskalender, optisch
   klar von verbindlichen Einträgen unterscheidbar.

**Rechte:** Verbindliche Einträge nur mit Schichtplanungsrecht (`PermissionEnum::SHIFT_PLANNER`,
backend-autorisiert via Policy). Wünsche darf jede*r **nur für sich selbst** eintragen –
faktisch nur User (Freelancer/Dienstleister haben keinen Login); verbindliche Zuordnungen
gibt es für alle drei Worker-Typen.

---

## 2. Abgestimmte Produktentscheidungen (13.07.2026)

| # | Entscheidung | Ergebnis |
|---|---|---|
| 1 | Selbst gesetzter „Frei"-Eintrag löst **verbindliche** Zuordnung auf? | **Ja** – mit Notification an Dienstplanende **und** Eintrag im Schichtverlauf/Activity-Log |
| 2 | Confirm-Dialog beim Terminverschieben | **Nur im Event-Bearbeiten-Modal** (Precheck-Endpoint); Drag&Drop/Bulk lösen ohne Dialog auf und benachrichtigen nur |
| 3 | Was „verwandelt" eine Zuordnung? | **Nur eine Schicht desselben Projekts** am selben Tag; fremde Projekte lösen nichts auf |
| 4 | PDF-Aushang | **Ja** – verbindliche Zuordnungen erscheinen im Schichtplan-PDF in den Tageszellen (analog zur Anzeige im persönlichen Einsatzplan) |
| 5 | Wunsch-Sichtbarkeit im Projekt | **Ja** (Ticket) – ShiftTab-Overview + Tagesbalken; der gegenteilige Absatz der alten Planfassung ist überholt |
| 6 | Wunsch auf Abwesenheitstag | Kein roher 422-Fehler: Frontend blockt mit verständlicher Warnung („Wunsch nicht möglich, an diesem Tag ist eine Abwesenheit eingetragen"); Backend validiert als Sicherheitsnetz mit übersetzter Meldung |
| 7 | Notifications für neue Wünsche | **Nein** – keine Benachrichtigung pro Wunsch (zu viel Rauschen); Sichtbarkeit über ShiftTab/Dienstplan reicht |
| 8 | Konflikt-Warndreieck „verbindlich zugeordnet + abwesend" | **Nein** – bei Ganz-Zeitraum-Zuordnungen sind einzelne Abwesenheiten normal, die Warnung würde inflationär |
| 9 | Anzeigeeinstellung ShiftTab | **Ein** Toggle (`show_project_assignments`, default an) für Overview + Tagesbalken-Avatare zusammen |
| 10 | `AddEditVacationsModal` erweitern? | **Nein** – eigenes geteiltes `ProjectAssignmentModal`, gemeinsame Einstiegspunkte |

**Umgesetzte QoL-Ergänzungen:** Unbesetzt-Dropdown priorisiert Zugeordnete (Badge) und
Wünsche; Wunsch-Annehmen per Klick (Typ-Flip zu verbindlich); Auto-Wiederherstellung nach
Entfernen der verwandelnden Schicht; Projekt-Highlight (Dimmen fremder Balken); vollständiges
Activity-Log; Multiedit zeigt nicht wählbare Projekte disabled mit „deckt X von Y Tagen ab".

---

## 3. Datenmodell – ein Eintrag pro Tag (Availability-Muster)

```
project_day_assignments
  id
  project_id                          // FK, cascade
  employable_type, employable_id      // polymorph wie shift_workers (User/Freelancer/ServiceProvider)
  date                                // ein Eintrag pro Tag
  type enum('binding','wish')
  group_id (uuid)                     // klammert einen Anlege-Vorgang (Serie)
  is_full_period boolean              // ganzer Projektzeitraum → steuert Mitverschieben
  created_by                          // FK users
  superseded_by_shift_id (nullable)   // welche Schicht die Zuordnung „verwandelt" hat
  created_at, updated_at, deleted_at  // Soft-Delete
  UNIQUE (project_id, employable_type, employable_id, date, type)
  INDEX (employable_type, employable_id, date)
```

Warum Tageszeilen statt Zeitraum-Zeile:
- per-Tag-Lookup in der Dienstplan-Zelle (wie `dayServices[date]`),
- Einzeltag-Auflösung („frei", Verwandeln, Löschen) = simples Row-Delete,
- Verschiebe-Edgecase sauber entscheidbar (`is_full_period` → re-materialisieren, sonst auflösen),
- Balken-Anzeige bekommt Serien-Grenzen nach dem `attachSeriesDateBounds`-Muster
  (`artwork/Modules/Availability/Models/Availability.php:133`).

**`superseded_by_shift_id`:** Verwandeln = Soft-Delete mit Schicht-Verweis statt Hard-Delete.
Wird die Schicht-Zuweisung später entfernt, wird die Projektzuordnung automatisch
wiederhergestellt (Person bleibt dem Projekt zugeordnet, nur die Konkretisierung entfällt).

---

## 4. Regeln

- **Verwandeln:** ShiftWorker-Hook dort, wo heute `assignUserToProjectIfNecessary` läuft
  (`ShiftUserService.php:143`, `ShiftWorkerService.php:533` – Duplikat, in gemeinsamen Service
  ziehen). Nur Schichten **desselben Projekts** am selben Tag.
- **„Frei"-Eintrag** (Hook im `checkVacation`-Flow, `VacationController.php:128`) löst die
  Zuordnung des Tages auf – auch verbindliche. Dann: Notification an Dienstplanende
  (`User::permission(PermissionEnum::SHIFT_PLANNER->value)` + `NotificationService`, Muster
  `WorkTimeChangeRequestController::store`) **und** Activity-Log-Eintrag, sichtbar im
  Schichtverlauf.
- **Wunsch + Verfügbarkeit** koexistieren. **Wunsch + Abwesenheit** schließen sich aus –
  beidseitig: Wunsch auf Abwesenheitstag → Warnhinweis (kein Speichern); neue Abwesenheit über
  Wunsch-Tage → Wunsch wird aufgelöst, Hinweis im Modal.
- **Projektteam:** Auto-Add via `project_user` nur bei `binding` (Wünsche nicht). Kein
  Auto-Remove beim Entfernen der letzten Zuordnung (Risiko stiller Team-Löschungen).
- **Audit:** Anlegen, Löschen, Verwandeln, Auflösen (frei/Verschiebung) und Wiederherstellen
  werden geloggt (ChangeBuilder/Spatie), mit Auflösungsgrund.

---

## 5. Dienstplan (`ShiftPlan.vue`)

**Anzeige – Balken als Per-Zellen-Segmente** (technische Abweichung zur alten Fassung):
Das untere User-Grid ist ein `Virtual2DGrid` mit virtualisierten Spalten – das absolute
Overlay aus `ArticleRow.vue`/`planningBars.js` würde gegen nicht gerenderte Zellen
positionieren und mit `expand_days`/KW-Extraspalten kollidieren. Stattdessen:

- Neues Computed `assignmentsToday` in `ShiftPlanCell.vue` aus
  `user.project_assignments?.[day.withoutFormat]`.
- Pro Zuordnung ein **5-px-Farbstreifen** am unteren Zellrand (deterministische Projektfarbe
  via `colorForIssue`-Hash aus `planningBars.js`), randlos an Nachbarzellen anschließend,
  abgerundete Enden nur an `series_start_date`/`series_end_date` → wirkt wie durchlaufender
  Balken, ist aber virtualisierungs- und `v-memo`-kompatibel. Max. 2 Lanes + „+n"-Punkt.
- **Verbindlich** = gefüllt; **Wunsch** = gestreift (`repeating-linear-gradient`-Muster der
  Extern-Ausgaben). In Listen (Modal, ShiftTab) zusätzlich kursiv (Kommentar-Konvention,
  `ShiftPlanCell.vue:245`).
- Projektname + Zeitraum on Hover; Zell-Klick öffnet wie bisher das Modal.
- **Highlight-Modus:** fremde Projektstreifen dimmen (`opacity-30`, Muster
  `ArticleRow.isHighlighted`).

**Zellen-Modal (`ShowUserShiftsModal.vue`):** neue Rubrik **„Projekte"** zwischen Schichten
und Verfügbarkeitsstatus: aktive Zuordnungen/Wünsche des Tages (Typ-Badge, „ganzer Zeitraum"/
Einzeltage, Löschen mit Wahl „nur dieser Tag" vs. „ganze Zuordnung") + Button
„Projekt zuordnen" → geteiltes `ProjectAssignmentModal`.

**Projekt-Auswahl (Picker im Modal):**
1. Sofort-Vorschläge: Projekte, in deren Zeitraum der Tag liegt (Batch-Muster
   `ProjectController::getProjectPeriods`, `ProjectController.php:464`).
2. Suchfeld: bestehender Endpoint `projects.search` – `ProjectSearchDTO` liefert bereits
   `{id, name, first_event_date, last_event_date, artists}` → Zeitraum steht bei jedem
   Vorschlag (DD.MM.YYYY), Sortierung nach Zeitraumnähe; ggf. nur `?date=`-Parameter ergänzen.

**Multiedit – zwei Stellen:**
- **Zellen-Multiedit** (`CellMultiEditModal.vue` → `shift.plan.user.cell.update`,
  `ShiftController.php:1401`): neue Sektion „Projekt zuordnen"; wählbar nur Projekte, deren
  Zeitraum **alle** selektierten Tage abdeckt; nicht passende Projekte disabled mit
  „deckt X von Y Tagen ab"; Backend validiert erneut.
- **Personen-Multiedit** (`userForMultiEdit` → `shift.multi.edit.save`): Checkbox neben dem
  Projekttitel „Person dem gesamten Projekt zuweisen" → legt `is_full_period`-Gruppe an.
  Bewusst nur Ganz-Zeitraum, keine Einzeltage.

**Unbesetzt-Dropdown (QoL):** Beim Besetzen einer Schicht stehen Personen mit verbindlicher
Zuordnung zu diesem Projekt an diesem Tag zuoberst (Badge „dem Projekt zugeordnet"), Wünsche
direkt darunter (kursiv/gestreift markiert).

**Payload/Performance:** `project_assignments` als per-Tag-Map in
`WorkerShiftPlanService::buildWorkerData` (`WorkerShiftPlanService.php:91`); **eine**
Batch-Query pro Worker-Typ in `WorkingHourService::getUsersWithPlannedWorkingHours`
(wie `availabilities`, `WorkingHourService.php:337`). Oberes Raum-Grid bleibt unberührt
(`room.__v` nicht betroffen). Nach Mutationen greift `reloadSingleWorker` – kein Voll-Reload.

---

## 6. Projekt-Schichten-Tab (ShiftTab)

- **Endpoint** `projects/{project}/day-assignments` (eine Query, nach Datum gruppiert,
  Personen-Basisdaten für Tooltips), geladen mit dem Meta-Call – **nicht** in den teuren
  Rooms-Batch.
- **Overview „Zugewiesene Personen"** oben im Tab, drei Gruppen: *Ganzes Projekt* /
  *Einzelne Tage* (Tagesliste pro Person, DD.MM.) / *Wünsche* (kursiv). Personen als
  `UserPopoverTooltip` mit `lazy-load` (modulweiter Cache vorhanden).
- **Wunsch annehmen (QoL):** Planer*innen können Wünsche im Overview per Klick in eine
  verbindliche Zuordnung umwandeln (Typ-Flip).
- **Tagesbalken** (`ShiftPlanDailyView.vue:147–210`): Avatare der an dem Tag zugeordneten
  Personen – verbindlich **rechts** vom Datum, Wünsche **links** mit gestricheltem grünem
  Ring; Cap ~5 Avatare + „+n"-Popover.
- **Anzeigeeinstellung:** ein Boolean `show_project_assignments` (default `true`) in
  `UserShiftPlanDailySettings` + Toggle in `FunctionBarSetting.vue`; gilt für Overview und
  Tagesbalken gemeinsam.

---

## 7. Einsatzplan & Verfügbarkeitskalender (Wünsche)

- **Geteiltes `ProjectAssignmentModal.vue`** (Modi `wish`/`binding`): Projekt-Picker wie oben,
  dann Radio „Gesamter Projektzeitraum (DD.MM.YYYY–DD.MM.YYYY)" vs. „Einzelne Tage" mit
  Mehrfach-Datumsauswahl. Einstiegspunkte: Einsatzplan-Tagesbutton (wish),
  ShowUserShiftsModal (binding; wish, wenn eigene Zelle ohne Planungsrecht),
  Verfügbarkeitskalender.
- **Einsatzplan (`UserShiftPlan.vue`):** neben dem „Individuelle Zeit"-Button (Z. 191) pro Tag
  ein Wunsch-Button (nur eigener Plan). Eigene **verbindliche Zuordnungen werden dem User in
  den Tageszellen angezeigt** (Erweiterung `UserShiftPlanPageDto` /
  `getDaysWithEventsAndTotalPlannedWorkingHours`) – gleiche Darstellung wandert ins PDF.
- **Verfügbarkeitskalender:** Wünsche als **Balken über die Tage** (Serien-Grenzen), kombinierbar
  mit Verfügbarkeit; bei Abwesenheit Warnhinweis statt Fehler. Rechts in `UserVacations.vue`
  dritte Sektion **„Projektwünsche"** – ein Eintrag pro Projekt mit Zeitraum
  (bestehendes `buildEntries`-Gruppierungsmuster, `UserVacations.vue:95`).
- `AddEditVacationsModal` bleibt unangetastet.

---

## 8. Terminverschiebung

Hook in `EventController::updateEvent` (Kaskade existiert für Schichten/Inventar,
`EventController.php:2083–2148`):

1. Nach dem Speichern neuen Projektzeitraum berechnen – nur reagieren, wenn sich min/max
   tatsächlich ändert.
2. `is_full_period`-Gruppen → Tageszeilen **re-materialisieren** (Transaktion: fehlende Tage
   anlegen, herausgefallene löschen).
3. Einzeltag-Einträge außerhalb des neuen Zeitraums → **auflösen + Dienstplanende
   benachrichtigen** + Activity-Log.
4. **Confirm-Dialog nur im Event-Modal:** Precheck-Endpoint
   (`GET events/{event}/reschedule-impact`) vor dem Submit; Dialog nach dem Vorbild
   `showSeriesEdit` in `EventComponent.vue` („…wird die Projektzuordnung für den DD.MM.YYYY
   von XXX aufgehoben, fortfahren?"). Drag&Drop im Kalender und Bulk-Edits: ohne Dialog,
   Notification-only.

Außerdem: Projekt-Papierkorb/Restore-Jobs bekommen eine Assignment-Kaskade (in Job-Kaskaden
nie über `$event->project` navigieren – bekannte Falle).

---

## 9. Notifications (bewusst schlank)

| Ereignis | Empfänger | Status |
|---|---|---|
| „Frei"-Eintrag löst verbindliche Zuordnung auf | Dienstplanende (SHIFT_PLANNER) | fest |
| Terminverschiebung löst Einzeltag-Zuordnung auf | Dienstplanende | fest |
| Neuer Wunsch | – | **entfällt** (Entscheidung 7) |
| Verbindliche Zuordnung angelegt/entfernt → betroffene Person | Person | vorgesehen (analog Schicht-Zuweisung), **finale Freigabe offen** |

---

## 10. PDF-Aushang

`ExportPDFController::createShiftPlanPDF`: verbindliche Zuordnungen erscheinen in den
Tageszellen der Person (Projektname, kompakt – wie im persönlichen Einsatzplan). Wünsche
erscheinen **nicht** im PDF. Achtung Seiten-Packing: Zusatzzeilen gehen in die
Höhenbudget-Rechnung ein (Muster serverseitiges Packing, Budget 820 px/Seite).

---

## 11. Aufwandsschätzung (überarbeitet)

### Phase 1 — Backend-Kern

| Aufgabe | h |
|---|---|
| Migration + Model `ProjectDayAssignment` (polymorph, type, group_id, is_full_period, superseded_by_shift_id, Soft-Delete) | 4 |
| Query-Service: Projekte am Tag / Zeitraum-Vorschläge, per-Tag-Maps, Serien-Grenzen | 5 |
| Controller + Routen + Policy (store Einzeltage/Zeitraum, delete Tag/Gruppe, wish nur self) | 7 |
| Verwandeln: ShiftWorker-Hook (nur selbes Projekt) + Auto-Wiederherstellung | 5 |
| „Frei"-Eintrag löst auf + Planer-Notification + Activity-Log | 4 |
| Zellen-Multiedit-Endpoint erweitern + Validierung „alle Tage im Zeitraum" | 3 |
| Team-Auto-Add + Dedup `ShiftUserService`/`ShiftWorkerService` | 3 |
| Dienstplan-Payload (Batch je Worker-Typ) | 4 |
| Activity-Log/ChangeBuilder für alle Mutationen | 3 |
| **Summe** | **38** |

### Phase 2 — Dienstplan-Frontend

| Aufgabe | h |
|---|---|
| `ProjectAssignmentModal` (geteilt): Picker mit Zeitraum-Vorschlägen + Suche, Radio Zeitraum/Einzeltage | 8 |
| Rubrik „Projekte" im `ShowUserShiftsModal` (Liste, Löschen Tag/Gruppe) | 4 |
| Zellen-Streifen in `ShiftPlanCell` (Lanes, Serien-Ränder, Hover, Wunsch-Streifenmuster) | 7 |
| Zellen-Multiedit-Sektion (Abdeckungs-Filter, disabled-Anzeige) | 4 |
| Personen-Multiedit-Checkbox „gesamtes Projekt" | 3 |
| Highlight/Dimmen fremder Projekte | 2 |
| Unbesetzt-Dropdown: Zugeordnete oben + Badge, Wünsche darunter | 3 |
| **Summe** | **31** |

### Phase 3 — ShiftTab + Einsatzplan

| Aufgabe | h |
|---|---|
| Endpoint `projects/{project}/day-assignments` + Laden im Meta-Flow | 3 |
| Overview „Zugewiesene Personen" (3 Gruppen, Lazy-Tooltips) | 6 |
| Tagesbalken-Avatare (Wünsche links gestrichelt-grün, verbindlich rechts, „+n") | 5 |
| Anzeigeeinstellung `show_project_assignments` (Migration, FunctionBarSetting, Gating) | 3 |
| Einsatzplan: Wunsch-Button pro Tag + eigene Zuordnungen in Tageszellen | 5 |
| Wunsch-Annehmen (Typ-Flip) durch Planer*innen | 2 |
| **Summe** | **24** |

### Phase 4 — Verfügbarkeitskalender (Wünsche)

| Aufgabe | h |
|---|---|
| Wunsch-Balken im Kalender (Serien-Grenzen) | 5 |
| Dritte Sektion „Projektwünsche" in `UserVacations.vue` | 3 |
| Abwesenheits-Ausschluss beidseitig mit Warnhinweisen (kein roher 422) | 4 |
| **Summe** | **12** |

### Phase 5 — Verschiebung, PDF, Querschnitt, Tests

| Aufgabe | h |
|---|---|
| Re-Materialisierung `is_full_period` bei Terminverschiebung | 4 |
| Einzeltag-Auflösung + Notification + Precheck-Endpoint + Confirm-Dialog im Event-Modal | 7 |
| PDF-Aushang: verbindliche Zuordnungen in Tageszellen | 4 |
| Papierkorb/Restore-Kaskade (Jobs) | 2 |
| Übersetzungen DE/EN | 2 |
| Pest-Tests (CRUD, Rechte, Verwandeln + Restore, Frei-Auflösung, Verschiebung, Wunsch-Ausschluss, Multiedit-Validierung) | 10 |
| Integration / QA / Review-Puffer | 8 |
| **Summe** | **37** |

### Gesamt

| Phase | h |
|---|---|
| 1 — Backend-Kern | 38 |
| 2 — Dienstplan-Frontend | 31 |
| 3 — ShiftTab + Einsatzplan | 24 |
| 4 — Verfügbarkeitskalender | 12 |
| 5 — Verschiebung + PDF + Querschnitt + Tests | 37 |
| **Gesamt** | **≈ 142 h** |

**Realistische Spanne: 125–155 h** (~3,5 Personenwochen).

### Optional Teil 3 — Gewerk → Projektrolle (separat, unverändert ≈ 12 h)

Migration `crafts` ↔ `project_role` + `show_as_project_role`-Flag, Häkchen in
Gewerk-Settings, Auto-Zuweisung der Rolle bei Schicht-/Projektzuordnung, Tests.

---

## 12. Relevante Dateien / Touchpoints

**Backend**
- `artwork/Modules/Project/Models/ProjectDayAssignment.php` *(neu)* + Migration
- `artwork/Modules/Project/Models/Project.php` (Relation, Team-Auto-Add)
- `artwork/Modules/Shift/Services/ShiftUserService.php:143` / `ShiftWorkerService.php:533` (Verwandeln-Hook, Dedup)
- `artwork/Modules/Vacation/Http/.../VacationController.php:128` (`checkVacation`, Frei-Auflösung)
- `artwork/Modules/Worker/Services/WorkerShiftPlanService.php:91` + `artwork/Modules/User/Services/WorkingHourService.php:337` (Payload)
- `app/Http/Controllers/EventController.php:2083` (Verschiebe-Kaskade) + Precheck-Endpoint *(neu)*
- `app/Http/Controllers/ShiftController.php:1401` (`updateUserCell`, Multiedit)
- `ExportPDFController::createShiftPlanPDF` (PDF)
- `UpdatePermissionsCommand.php` + `BaseDataProvider.php` (falls neue Permission nötig)

**Frontend**
- `resources/js/Pages/Shifts/Components/ShiftPlanCell.vue` (Streifen)
- `resources/js/Pages/Shifts/Components/ShowUserShiftsModal.vue` (Rubrik „Projekte")
- `resources/js/Pages/Shifts/Components/CellMultiEditModal.vue` + `ShiftPlan.vue` (Multiedit)
- `resources/js/Pages/Shifts/ShiftPlanDailyView.vue:147` (Tagesbalken) + ShiftTab-Overview *(neu)*
- `resources/js/Artwork/Filter/FunctionBarSetting.vue` + `UserShiftPlanDailySettings` (Setting)
- `resources/js/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue` (Wunsch-Button, eigene Zuordnungen)
- `resources/js/Pages/Users/Components/UserVacations.vue` (Sektion „Projektwünsche")
- `ProjectAssignmentModal.vue` *(neu, geteilt)*
- `planningBars.js` (`colorForIssue`-Hash wiederverwenden)

---

## 13. Offene Punkte

- **Notification an die betroffene Person** bei Anlegen/Entfernen einer verbindlichen
  Zuordnung: vorgesehen (analog Schicht-Zuweisung), finale Freigabe steht aus.
- Teil 3 (Gewerk → Projektrolle): Beauftragung offen; bis dahin Team-Add ohne Rolle.
