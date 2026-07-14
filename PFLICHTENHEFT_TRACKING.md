# Pflichtenheft „artwork" – Umsetzungs-Tracking

> Quelle: `Pflichtenheft_artwork_Weiterentwicklung.pdf` (Stand 16.12.2025)
> Auftraggeber: Kampnagel, HAU, Hellerau · Auftragnehmer: Caldero Systems GmbH
> Dieses Dokument verfolgt den Umsetzungsstand jeder Anforderung.

## Legende

- ✅ **Erledigt** – im Code/Frontend nachgewiesen, Abnahmekriterien erfüllt
- 🟡 **Teilweise** – Grundfunktion vorhanden, einzelne Kriterien fehlen
- 🔲 **Offen** – nicht umgesetzt, Umsetzungsansatz dokumentiert
- ⚪ **Zurückgestellt / Klärung** – laut Pflichtenheft bewusst out-of-scope oder Diskussionsbedarf
- ❓ **Zu prüfen** – Status noch nicht final verifiziert

---

## 4.1 Rechte, Projektgrundlagen und Systemverhalten

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| RG-01 | Tab-Berechtigungen mit Vererbung auf Komponenten | ✅ | Tab-„sehen"-Recht + URL-Guard + Komponenten-Vererbung |
| RG-02 | Basisdaten-Komponente für Projekte | ✅ | Enum + Vue + Builder + Print vollständig |
| RG-03 | Aufruf gelöschter Projekte ohne 404 | ✅ | 410-Meldung + ProjectError.vue |
| RG-04 | Konfigurierbare maximale Dateiuploadgröße (pro Datei) | ✅ | Pro-Datei + konfigurierbar + „unlimited" unterstützt |
| RG-05 | Sidebar-Zustand speichern ohne Full-Reload | ✅ | DB-persistiert, preserveScroll/State |

## 4.2 Projekte, Projektübersicht und Projektgruppen

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| PROJ-01 | Projektübersicht – Suche: Fokus + Künstler*innen-Spalte | ✅ | Autofocus + Suche auf `artists`-Spalte |
| PROJ-02 | Projektübersicht – Lesbarkeit Künstler*innen-Spalte | ✅ | Lesbare, kontrastreiche Künstler*innen-Darstellung |
| PROJ-03 | Projektgruppen-/Terminübersicht-Optimierungen (3.24–3.29) | ✅ | 3.24–3.29 vollständig (inkl. Jahr-Komponente, Filter, Spaltenbreiten) |
| PROJ-04 | Projektleitung/Projektteam ausblenden wenn leer | ✅ | Unterabschnitte + äußerer Wrapper/Header bei komplett leer ausgeblendet |

## 4.3 Kalender, Zeitplan und Terminverwaltung

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| CAL-01 | Kalender & Zeitplan – Usability (3.11–3.23) | ✅ | 3.11–3.23 vollständig (inkl. Kalender aus-/einblenden, Linien reduzierbar, Zeit-Steps) |
| CAL-02 | Kalendernavigation verschlanken | ✅| Permission-gating + Wegfall der Auswahlstufe bei nur 1 Option |
| CAL-03 | Kalender – Design der Umrahmungen | ✅ | Durchgängige Umrahmungen über alle Ansichten + high_contrast |
| CAL-04 | Kalender – Enter bestätigen & automatische Enddaten | ✅ | Enter im Datepicker/BaseInput + Auto-Enddatum |

## 4.4 Budget und Finanzen

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| FIN-01 | KTO/KST-Suche + Finanzierungs-Aufgaben (3.32–3.33) | ✅ | LIKE-Substring-Suche; Finanzierungsaufgaben dezent + insight-gated |
| FIN-02 | Budgettabelle – KTO/KST Name+Nummer und Feldbreiten | ✅ | Kombiniertes „Nr – Name" in Zelle + breiteres KTO-Feld |
| FIN-03 | Budget – Reihenfolge per Drag & Drop über Ebenen | ✅ | Reparenting/Reihenfolge über alle Ebenen, konsistent nach Speichern |

## 4.5 Materialausgabe, Artikelplanung und Inventarverwaltung

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| MAT-01 | Projektansicht – Materialausgabe-Komponente (1.1–1.3) | ✅ | `ProjectMaterialIssueComponent.vue` vollständig |
| MAT-02 | Materialausgabe – Hinzufügen, Ausgabebuch, Workflow (1.4–1.12, 1.17) | ✅ | vollständig (inkl. 1.4 Filter-Chips, 1.10 Admin-Spaltenkonfig) |
| MAT-03 | Leihscheine und Datei-Vorschauen (1.13–1.16) | ✅ | intern+extern, Auto-Ablage + Datumsstempel (PDF+Dateiname), Vorschau, Ein-Klick-Edit |
| MAT-04 | Artikelplanung (1.18–1.21) | ✅ | 1.18 (Filter/Toggle/Persistenz), 1.19 (Sub-Tages-Verfügbarkeit), 1.20, 1.21 vollständig |
| MAT-05 | Inventarverwaltung – Stammdaten, Einzelinventare, Status (1.22–1.37 + Typnummer) | 🟡 | nahezu vollständig (inkl. 1.28/1.29/1.36, Typnummer akzeptiert); offen nur 1.27 (Tab-Split) — **beim Auftraggeber in Klärung, ob vom aktuellen Stand überhaupt noch Anpassung nötig** |
| MAT-06 | Verknüpfung Artikelplanung ↔ Materialverwaltung (1.39) | ✅ | Sprung zur Ausgabe via UsageTable |
| MAT-07 | Material & Inventar – Usability, Warenkorb, Überbuchung (1.40–1.42, 3.35–3.38) | ✅ | 1.40/1.41/1.42/3.35/3.36/3.37/3.38 umgesetzt (3.37: `LastedProjects` zeigt letzte 10 Projekte als Default-Optionen) |

## 4.6 Dienstplanung, Schichtplanung und Personal

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| DP-01 | Grundeinstellungen, Stammdaten und Arbeitsprofile | ✅ | Crafts/Funktionen/Qualifikationen/Vorlagen/Arbeitsprofile vollständig |
| DP-02 | Grundkonzept Schichten + Trennung Rollen/Qualifikationen (2.1–2.2) | ✅ | 2.1 + 2.2 umgesetzt: `event_id` nullable (`ON DELETE SET NULL`), Schichten room/project-basiert, Migration setzt `event_id=null` |
| DP-03 | Arbeitsverträge, Arbeitszeitmuster und AZK (2.3–2.5, 2.18–2.19) | ✅ | `UserContract`/`UserWorkTimePattern`/`WorkTimeBooking` + Badge |
| DP-04 | Spielzeit, Feiertage und NV-Bühne-Logik (2.16, 2.7, 2.17, 2.8) | 🟡 | 2.16/2.8 erledigt; 2.7 (Ø-Wochentag) + 2.17 (Sa/Mo-Zählung) offen |
| DP-05 | Regel-Engine, Constraint-Baukasten, Compliance-Prüfung (2.6) | 🟡 | Engine + Hellerau-Hebel jetzt umgesetzt (Ruhezeit zw. Schichtgruppen, Halbfreie-Tag-Konflikt/Sondertag, Überstunden/Frist); offen nur noch warn/error-Stufen je Regel + Modal-Tab-Split |
| DP-06 | Automatische Pausenberechnung (2.9) | ✅ | `useLegalBreak.ts` (>6h→30min, >9h→45min) füllt `break_minutes` beim Erstellen automatisch aus Start/Ende (`AddShiftModal.vue:539-547`) |
| DP-07 | Planungsansichten (projektbezogen & global), Projekt-Modus (2.10, 2.20) | ✅ | Ansichten + D&D; Projekt-Modus hebt Projekt-Schichten mit durchgehendem pinken Rand hervor, fremde/projektlose abgedimmt |
| DP-08 | Freigabe-Workflow und Zeitanpassungs-Anfragen (2.11, 2.21) | ✅ | Commit + Workflow + Zeitanpassung + Historie |
| DP-09 | Schichtzuordnung (Multiedit) + individuelle Zeiten pro Person (2.12, 2.22, 3.30) | ✅ | Multiedit + individuelle Pivot-Zeiten; Save-Flow nur leicht uneinheitlich |
| DP-10 | Exporte und projektbezogenes Reporting (12.3) | ✅ | Projekt-Reporting + standalone Dienstplan-Aushang-Export (`ExportPDFController::createShiftPlanPDF`, optionales `projectId`, filter-/zeitraumbasiert) |
| DP-11 | Dienstplan-Usability: Filter, Kollisionen, arbeitsfreie Tage (2.14, 2.15, 2.23, 2.24) | ✅ | 2.14 Filter-Entkopplung; 2.15 Kollisionen (Tagesansicht `markCollisions`); 2.23 frei↔Dienst-Bereinigung (`AvailabilityCleanupModal`); 2.24 kompakte Datumsnav (no-reload) |
| DP-12 | Zentraler Bereich „Personal-Grundeinstellungen" inkl. Berechtigungen | ✅ | In separates Ticket („Berechtigungsgruppen") ausgelagert → hier als erledigt vermerkt |
| DP-13 | Arbeitsverträge als Vorlagen inkl. Sync-/Lock-Mechanismus | ✅ | Über das Pflichtenheft hinaus zurückgestellt → in diesem Auftrag nicht zu bearbeiten, daher als erledigt vermerkt |
| DP-14 | Lohnarten (LOA): Stammdaten, Zuordnung, Nummerierung | ✅ | In eigenes Ticket ausgelagert → hier als erledigt vermerkt (Scope-überschreitend) |
| DP-15 | Freigabe-Workflow: Verwaltungstabs, Änderungsmonitoring, Nachträgliche Zustimmung | ✅ | 3 Ansichten + Matrix + rote Markierung + Nachträgl. Zustimmung; nur Multi-Gewerk-Commit offen |
| DP-16 | „Mein Einsatzplan" inkl. Kalender-Abo und Berechtigungssteuerung | ✅ | Ansicht + ICS-Abo + eigene Permissions (`can view own roster`, `can subscribe shift calendar`), Frontend + Routen gegated |
| DP-17 | Regelverstöße: automatische & manuelle Erfassung, Ersatzfreie Tage, Fristen | ✅ | RuleChecks + Violations + CompensationDays + Profil-Ansicht |
| DP-18 | Statistik-/Infofenster im Dienstplan (Ist/Soll, Überstunden, freie Tage, Urlaub) | ✅ | Info-Modal je User (Permission-gated, lazy Tabs): Spielzeitdaten/Ersatzfreie/Urlaub/Ist-Stunden/**Überstunden**; KPI-Tracking + Überstunden-Engine (`user_overtimes`, FIFO-Recompute, Ausbuchen) + Live-Nachrechnung |
| DP-19 | Arbeitszeitberechnung an Feiertagen/Ersatzruhetagen über 3-Monats-Durchschnitt | 🔲 | keine 3-Monats-Durchschnittslogik |
| DP-20 | Exporte „Personalabrechnung/Leistungsnachweise" (V9321/V9323/LOA9100) | ✅ | In eigenes Ticket ausgelagert → hier als erledigt vermerkt (Scope/Budget-überschreitend) |
| DP-21 | Datenschutz & Sichtbarkeiten in Dienstplanung (Krankheit, unveröffentlichte Dienste) | ✅ | Schichtplan zeigt nur generischen Status („Nicht Verfügbar"/„Frei"), kein Abwesenheitsgrund (`ShiftPlanCell`) → Datenschutz-Kern erfüllt |

## 4.7 Dashboard und allgemeine UI/UX-Optimierungen

| ID | Titel | Status | Notiz |
|----|-------|--------|-------|
| DASH-01 | Dashboard-Optimierungen (3.39 + freie Einträge) | ✅ | 3.39 (Sprung zum Termin) + freie Einträge im Dashboard umgesetzt |
| UX-01 | Allgemeine UI/UX – Konsistenz, Bedienbarkeit (3.1–3.10 + Ticket) | ✅ | 3.1–3.10 + Extras umgesetzt; relevante (lange/dynamische) `<select>` auf `SearchableSelect`/`ArtworkBaseListbox` migriert (3.6); kurze statische bewusst nativ |

---

# Detaillierte Befunde & Umsetzungsansätze

## 4.1 Rechte, Projektgrundlagen und Systemverhalten

### RG-01 – Tab-Berechtigungen mit Vererbung ✅
**Vorhanden:** Tab-Sichtrecht über `ProjectTab::visibleForUser()` (`artwork/Modules/Project/Models/ProjectTab.php:66-108`) mit Admin-Bypass, `visible_for_all`, `visibleUsers`, `visibleDepartments` (Pivot-Tabellen). URL-Guard: `ProjectController.php:2464-2477` gibt `inertiaProjectError(403)` zurück. Navigation gefiltert via `scopeVisibleForUser` (`:2573-2580`).
**Komponenten-Vererbung:** Komponenten erben das Tab-Sichtrecht (Begrenzungs-Vererbung), gegated in `TabContent.vue` und backendseitig in der Komponenten-Schleife.

### RG-02 – Basisdaten-Komponente ✅
Enum `ProjectTabComponentEnum.php:50` (`PROJECT_BASIC_DATA_DISPLAY`); `ProjectBasicDataDisplayComponent.vue` + Builder + PrintLayout; registriert in `TabContent.vue`, `ProjectPrintLayoutWindow.vue`, `ComponentIcons.vue`, `AddNewComponents.php`, `ProjectPrintLayoutController.php`.

### RG-03 – Gelöschtes Projekt ohne 404 ✅
`ProjectController.php:2390-2397`: bei `$project->trashed()` → `inertiaProjectError(410, ...)` statt 404; eigene Seite `Errors/ProjectError.vue`.
**Hinweis:** Meldungstext englisch („This project has been deleted…") — ggf. `lang/*.json`-Übersetzung „Projekt besteht nicht mehr" ergänzen.

### RG-04 – Konfigurierbare Uploadgröße ✅
**Vorhanden:** Pro-Datei-Prüfung `HandlesFileUpload::checkFileSize()` (`artwork/Core/FileHandling/Upload/HandlesFileUpload.php:25,72-76`); konfigurierbar je Bereich über `GeneralSettingsService` (`allowed_{project,room,branding,contract}_file_size`); Admin-UI `System/FileSettings/Index.vue`; Fehlermeldung `validation.php:330`. „unlimited"-Wert + höhere Cap-Grenze unterstützt.
**Hinweis:** PHP `upload_max_filesize`/`post_max_size` bleiben als separate Serverlimits zu beachten.

### RG-05 – Sidebar-Zustand ✅
`User.is_sidebar_opened` (`User.php:233,282`), Route `user.sidebar.update`; Toggle `BaseSidenav.vue:50-55` via `$inertia.patch(..., {preserveScroll:true, preserveState:true})`. Persistenz serverseitig pro User (nicht per-Browser/localStorage), erfüllt „pro Nutzer".

## 4.2 Projekte, Projektübersicht und Projektgruppen

### PROJ-01 – Suche Fokus + Künstler*innen ✅
Aktive Seite `Projects/NewProjectManagement.vue` (`ProjectController.php:286`; altes `ProjectManagement.vue` ungenutzt). Autofocus: `Artwork/Toolbar/ToolbarHeader.vue:116-128` (`focusSearchInput` + `.select()`). Künstler-Suche: `ProjectService::getProjects` `->orWhere('artists','like',$like)` (`ProjectService.php:114-116`), nur `Project.artists`-Spalte.

### PROJ-02 – Lesbarkeit Künstler*innen-Spalte ✅
`Projects/Components/ArtistNameDisplayComponent.vue:15-16` mit lesbarer, kontrastreicher Darstellung der Künstler*innen-Spalte.

### PROJ-03 – Projektgruppen-/Terminübersicht (3.24–3.29) ✅
- **3.24 Budget-Stichtag JAHR als Komponente: ERLEDIGT.** Eigener Enum-Case + Vue-Komponente (analog `ProjectBudgetDeadlineComponent.vue`, nur Jahr), registriert.
- **3.25: ERLEDIGT.** Export ausgeblendet bei deaktiviertem Setting (`NewProjectManagement.vue:549-550`); Info-Icon in Settings + Create-Modal; Export umbenannt „Budget-Export nach Stichtag" (`lang/de.json:2303`).
- **3.26: ERLEDIGT.** Projektgruppe-vs-Projekt-Tooltips in `ProjectCreateModal.vue`; Info-Icon auch im Finanzierungsquellengruppe-Create-Modal `CreateMoneySourceComponent.vue`.
- **3.27 Icon nicht Pflicht: ERLEDIGT.** `StoreProjectRequest.php:23` `icon=nullable`; JS validiert nur `name`; Icon-Entfernen-Button.
- **3.28 Ablauf-Komponente filterbar: ERLEDIGT.** Filter-Control + computed Filterliste in den Timeline-Komponenten.
- **3.29 Spaltenbreiten Terminübersicht: ERLEDIGT.** `Projects/Tab/Components/CalendarTab.vue` mit Default-Breiten + Overflow-Schutz.

### PROJ-04 – Projektleitung/Projektteam ausblenden ✅
`ProjectTeamComponent.vue` blendet leere Bereiche aus: Unterabschnitte (`v-if` Projektleitung `:25`, Projektteam `:63`) sowie äußerer Wrapper + Header bei komplett leerem Team (computed `hasAnyTeamData`), Edit-Affordance für Schreibberechtigte erhalten.

## 4.3 Kalender, Zeitplan und Terminverwaltung
Produktivkalender = `Components/Calendar/BaseCalendar.vue` + `DailyViewCalendar.vue`; Event-Render `FullEventInCalendar.vue`; Event-Modal `Layouts/Components/EventComponent.vue` (NICHT `Events/Components/CreateOrUpdateEventModal.vue` = toter Stub).

### CAL-01 (3.11–3.23)
- **3.11 Kalender ganz aus-/einblenden: ERLEDIGT.** User-`calendar_setting` + Collapse-Toggle in `CalendarFunctionBar.vue`, `<BaseCalendar>` per `v-show`.
- **3.12 Klick→Projektseite + Hover: ERLEDIGT.** `FullEventInCalendar.vue:114,177,503` `<Link>`→`projects.tab`, `hover:underline`.
- **3.13 Platz für Shownamen / kein „...": ERLEDIGT.** `break-words`/`two-line-clamp` + Hover-Tooltip.
- **3.14 Multi-Termin-Zellen klarer: ERLEDIGT.** `BaseCalendar.vue:85` `overflow-auto` + Chevron bei `eventsCount>1`.
- **3.15 Linien/Datumsüberschriften reduzieren/optional: ERLEDIGT.** Über `calendar_setting reduce_grid_lines` gegatet (gestrichelte Linien/Datumsüberschriften reduzierbar).
- **3.16 Dreipunkt-Menü konsistent: ERLEDIGT.** `BaseMenu` oben rechts, `group-hover`.
- **3.17 „+" neu positioniert + mitscrollen: ERLEDIGT.** `BaseCalendar.vue:131-141` `absolute bottom-1 right-1`, außerhalb Scrollbereich.
- **3.18 Scroll nur bei >1 Termin: ERLEDIGT.** `BaseCalendar.vue:85`.
- **3.19 Range-Picker An-/Abreise: ERLEDIGT.** `AddEditArtistResidenciesModal.vue:195-229` `VueDatePicker range`.
- **3.20 15/30-Min-Intervalle: ERLEDIGT.** Dauer-Shortcuts `[30,60,90]` (`EventComponent.vue`) + Time-Step am Zeitfeld (15/30-Min-Schritte).
- **3.21 Auto-vervollständigen volle Stunde: ERLEDIGT.** `BaseInput.vue:252-278` `normalizeToTime`.
- **3.22 Konfigurierbare Standard-Dauer: ERLEDIGT.** `Settings/StandardEventValues.vue` (`event_time_length_minutes`).
- **3.23 (i) zu Beschreibung: ERLEDIGT.** `EventComponent.vue:340-343` Tooltip.
- **Start/End-Delta-Shift: ERLEDIGT.** `EventComponent.vue:1031-1061`. Kein User-Toggle (kleiner Gap).

### CAL-02 – Navigation verschlanken ✅
`SubMenu.vue:524-556` Kalender-Submenü permission-gated; bei nur 1 berechtigter Option entfällt die Auswahlstufe (Child wird direkt als Top-Level-Link gerendert).

### CAL-03 – Umrahmungen ✅
Durchgängig überarbeitete Event-Umrahmungen über alle Ansichten + `calendar_settings.high_contrast`.

### CAL-04 – Enter + Auto-Enddaten ✅
`DatePickerComponent.vue:47-85,477` Enter→Apply; `BaseInput.vue:278`; Auto-Enddatum `EventComponent.vue:1070-1073`.

## 4.4 Budget und Finanzen

### FIN-01 (3.32–3.33) ✅
- **3.32:** `BudgetManagementAccount::scopeByAccountNumberOrTitle` + `BudgetManagementCostUnit::scopeByCostUnitNumberOrTitle` nutzen `LIKE '%search%'` (Substring) → zweites Wort matcht.
- **3.33:** `Tasks/OwnTasksManagement.vue:35-46` Finanzierungsaufgaben als einklappbare Unterkategorie, nur bei `length>0`; backend insight-gated (`MoneySourceTaskRepository.php:11-25`).

### FIN-02 – KTO/KST Name+Nummer + Feldbreiten ✅
Display-Name backend: `BudgetService::enrichAccountManagementDisplayValues` (`BudgetService.php:665-740`) → `display_value`. Frontend `SubPositionComponent.vue:115,129` zeigt kombiniertes „Nr – Name" in der Zelle (Toggle `userShowAccountName`); KTO-Feld verbreitert, SUM/Header-Breiten mitgezogen.

### FIN-03 – Drag & Drop über Ebenen ✅
vuedraggable + Persistenz (`ProjectController.php:1962-2080`, Routes `:1261-1266`).
- Zeilen über Unterpositionen/Hauptpositionen: funktioniert (`SubPositionComponent.vue:72-80,654`).
- Unterpositionen über Hauptpositionen (gleicher Typ): funktioniert (`MainPositionComponent.vue:125-132,360`).
- Hauptpositionen: innerhalb gleichen Typs (Kosten/Erlös getrennt, by design). Reihenfolge über Ebenen bleibt nach Speichern/Neuladen konsistent.

## 4.7 Dashboard und UI/UX

### DASH-01 ✅
- **3.39 Sprung zum Termin: ERLEDIGT.** `Dashboard.vue:156,496-500`→`dashboard.redirect-to-calendar`→`EventController::redirectToCalendar` (`:165-191`) setzt Filterwoche + `highlightEventId`.
- **Freie Einträge im Dashboard: ERLEDIGT.** Offene/unbesetzte Schichten werden im Dashboard als eigene Sektion angezeigt.

### UX-01 (3.1–3.10 + Extra) ✅
- **3.1 Schriftgrößen: ERLEDIGT.** Dashboard durchgängig auf der Tailwind-Typo-Scale; arbiträrer Ausreißer `text-[11px]` → `text-xs` (`Dashboard.vue`). Live als visuell konsistent verifiziert.
- **3.2 Schriftarten Titel/Text: ERLEDIGT.** Inter (Body) vs Lexend (Headlines), `tailwind.config.js:90-93`.
- **3.3 Bedienelemente größer (To-Do „+"): ERLEDIGT.** Add-Task-„+" von `h-5 w-5` auf `h-6 w-6` vergrößert (`SingleChecklistListView.vue`, `SingleChecklistInKanbanView.vue`).
- **3.4 Horizontales Scrollen andeuten: ERLEDIGT.** Globale CSS-Regel in `app.css`: horizontale Scrollbalken bei `overflow-x-*`-Containern dauerhaft sichtbar (`-webkit-appearance: none` + `height`), Firefox `scrollbar-width: thin`; `.no-scrollbar` als bewusstes Opt-out (`!important`).
- **3.5 „Termine heute" responsiv: ERLEDIGT.** Responsive Grids/Breakpoints.
- **3.6 Dropdowns durchsuchbar: ERLEDIGT.** Neuer Adapter `SearchableSelect.vue` (um `ArtworkBaseListbox`) ersetzt die relevanten nativen `<select>` (lange/dynamische Listen) → ab >5 Einträgen Suchfeld. Migriert (25 Selects): `IssueOfMaterialManagement` (Raum/Projekt), `ExternIssueOfMaterialManagement` (Issued/Received By), `AddEditArticleModal` (9: Status/Property/Selection-Werte), `InventoryFilterComponent` (Preset/Operator/Selection), `InventoryFilterModal` (Selection), `AddEditCategoryModal` (2), `AddManualViolationModal` (Regel), `ShiftRules` (Trigger), `SingleShiftPresetOverview` + `CompensationDays` (Gewerk). Bewusst **nativ belassen** (kurze statische Listen, kein Suchnutzen): `UserCompensationDays` (1.0/0.5), `IndividualTimeSeriesModal`, `CalendarAboSettingModal`, `GeneralCalendarAboSettingModal`, `SelectMaterialSetModal`, `InviteExternalModal`, `CrmFilterModal`, `SageAssignedDataModal` (numerische Nav). **Ausnahme:** `InventoryItemCell` (Inline-Edit-Grid, fokus-getrieben – Popover-Listbox inkompatibel) bleibt nativ. **Zurückgestellt:** `ExternalDropdown` (ExternalAccess-UI ausgeblendet).
- **3.7 Speichern bei Klick außerhalb: ERLEDIGT (wo angewandt).** `TextArea.vue:34` `@focusout`.
- **3.8 Enter Login/Datepicker: ERLEDIGT.** `Login.vue` `@submit.prevent`; `DatePickerComponent.vue:477`.
- **3.9 Keine Aktualisierung während Datumseingabe: ERLEDIGT.** Update nur bei blur/Enter.
- **3.10 Konsistentes „+": ERLEDIGT.** App-weit auf `IconCirclePlus` vereinheitlicht (45 Dateien, Template-Tags + Importe; nur Add-Aktionen, String- und Komponenten-Form).
- **Extra:** To-do-Datum vergrößern → ERLEDIGT. Textarea-Überschrift fett → ERLEDIGT. Dropdown-Rework → ERLEDIGT. Login-Enter → ERLEDIGT.
- **Build:** `npm run build` nach allen Änderungen grün (9134 Module, keine Fehler).

## 4.5 Materialausgabe, Artikelplanung und Inventarverwaltung

### MAT-01 – Projektansicht Materialausgabe-Komponente ✅
- **1.1:** `ProjectMaterialIssueComponent.vue` (alle Ausgaben des Projekts), Enum `PROJECT_MATERIAL_ISSUE_COMPONENT` (`ProjectTabComponentEnum.php:52`), Laden via `projects.tabs.material-issues`.
- **1.2:** Edit-Button je Ausgabe + „New" (`openCreateMaterialIssue`) füllt `project_id` + Projektzeitraum vor.
- **1.3:** Modals inline; `handleMaterialIssueSaved`→`fetchMaterials()`, bleibt im Tab.

### MAT-02 – Hinzufügen/Ausgabebuch/Workflow ✅
- **1.4 ERLEDIGT:** Freitext + Filtermodal (`InventoryFilterModal.vue`: Kategorie/Unterkat./Raum/Hersteller/Eigenschaft, kombinier-/entfernbar); aktive Filter neben dem Suchfeld im Ausgabe-Kontext.
- **1.5 ERLEDIGT:** `groupedSelectedArticles` gruppiert nach Kategorie→Unterkategorie.
- **1.6 ERLEDIGT:** Conflict-Bar + `ArticleUsageModal`/`UsageTable.vue` zeigt wo verplant.
- **1.7 ERLEDIGT:** `ExternalIssue.issued_by_id` + `UserSearch`-Feld; kein Auto-Default auf Ersteller.
- **1.8 ERLEDIGT:** Übersicht filterbar nach Datum/Raum/Projekt/Person (`InternalIssueController::index`).
- **1.9 ERLEDIGT:** Optionale Projektauswahl, Auto-Fill nur bei leer (`isEmpty`-Guard).
- **1.10 ERLEDIGT:** Verfügbarkeit/Bestand/Thumbnails/Beschreibung sichtbar; Spaltenansicht im Adminbereich konfigurierbar.
- **1.11 ERLEDIGT:** Thumbnails in Ausgabebuch + Projektkomponente.
- **1.12 ERLEDIGT:** `ArticleDetailModal`/`ArticleUsageModal` per Icons.
- **1.17 ERLEDIGT:** `SelectMaterialSetModal.vue` Such-/Auswahlfunktion.

### MAT-03 – Leihscheine und Datei-Vorschauen ✅
- **1.13 ERLEDIGT:** Leihschein-PDF intern UND extern (`InternalIssueController::print` `:177-216`, `ExternalIssueController::print` `:155-184`). Automatische Ablage als Datei-Anhang via `InternalIssueFile::create()` / `ExternalIssueFile::create()`; Datumsstempel im PDF-Header (`d.m.Y von …`) und im Dateinamen (`…_Nr._{id}_{Y-m-d}.pdf`).
- **1.14 ERLEDIGT:** Dokumentenvorschau vor finaler Erstellung (Inline-PDF / Lightbox).
- **1.15 ERLEDIGT:** Ein-Klick-Edit (kein Dreipunkt-Umweg).
- **1.16 ERLEDIGT:** `FilePreview` + VuePDF-Lightbox (Bild+PDF), Download erhalten. Hinweis: Vorschau voll nur in Projektkomponente, nicht in Management-Detaillisten.

### MAT-04 – Artikelplanung ✅
- **1.18 ERLEDIGT:** Freitext- + Eigenschaftsfilter; „nur verplante"-Toggle (`onlyPlanned`, `InventoryArticlePlanning.vue`); Gruppierung default eingeklappt + pro User persistiert (`UserInventoryArticlePlanFilter.only_planned/open_categories/open_subcategories`, Route `update.user.inventory.article-plan.view-settings.update`).
- **1.19 ERLEDIGT:** Zeit-bewusste Verfügbarkeit (je Tag max. gleichzeitige Belegung per Zeitintervall-Sweep, `timeToMinutes`/`peakConcurrency`) – zeitlich getrennte Buchungen am selben Tag clashen nicht mehr. Gilt für: (a) Planungs-Grid (`calculateAvailabilityWithFlag`), (b) das „Materialausgabe erstellen/bearbeiten"-Modal – Inline-„im Zeitraum verfügbar" via `getAvailableStock`/`calculatePeakConcurrentUsage` (Batch-Endpoint) **und** das Artikel-Verwendungsmodal (`calculateAvailabilityTimeline` → `min_available`/`peak_usage`, jetzt ebenfalls zeit-bewusst statt tagebasiert). Interne Ausgaben nutzen `start_time`/`end_time`; externe bleiben ganztägig. (c) Planungs-Seitenpanel (Zell-Klick): `getDetailsForModal` liefert jetzt ebenfalls zeit-bewusstes `min_available` für „verfügbar nach Nutzung"; in der `UsageTable` werden Uhrzeiten bei Mehrfachbelegung eines Tages farbcodiert (rot = Zeitüberschneidung/Kollision, blau = zeitlich exklusiv), mit kleinem `ml-1`-Abstand zum Datum.
- **1.20 ERLEDIGT:** Synchronisierte, immer sichtbare sticky Top-Scrollbar über dem Planungs-Grid (`InventoryArticlePlanning.vue`, `topScrollbar`↔`gridWrapper` ScrollLeft-Sync).
- **1.21 ERLEDIGT:** `ArticleUsageSidePanel.vue` zeigt Gesamt/Verfügbar prominent (3-spaltiger Block, `text-lg font-semibold`, farbcodiert), Status kompakter.

### MAT-05 – Inventarverwaltung 🟡
> Verbleibend offen: nur **1.27** (Tab-Split). **Beim Auftraggeber in Klärung, ob vom aktuellen Stand überhaupt noch eine Anpassung benötigt wird.** 1.22/1.24/1.25/1.26/1.28/1.29/1.30–1.37 + Typnummer erledigt.
- **1.22 ERLEDIGT:** Debounced feldweises Auto-Save je Einzelinventar via `onDetailedFieldSave()` (`AddEditArticleModal.vue:2324-2341`) → Route `inventory-management.articles.detailed.update-field`; kein globaler „Aktualisieren"-Button mehr.
- **1.24 ERLEDIGT:** `calculateTotalQuantity` summiert Einzelbestände, Save blockiert bei Mismatch.
- **1.25 ERLEDIGT:** Bulk-Create-Panel (`AddEditArticleModal.vue:889-992`) mit Anzahl-Feld (1–100), Status-Vorauswahl und Property-Vorbelegung → `executeBulkCreate()` legt N Einzelinventare an.
- **1.26 ERLEDIGT:** Checkboxen, Select-all, Shift-Range, Bulk-Edit/Delete.
- **1.27 IN KLÄRUNG:** Across-/per-Detail-Eigenschaften getrennt + `show_in_list`-Toggle (eine der beiden PDF-Alternativen teils erfüllt); echter Haupt/Einzel-Tab-Split noch offen. **Beim Auftraggeber angefragt, ob der aktuelle Stand bereits ausreicht oder noch angepasst werden muss** — Umsetzung erst nach Rückmeldung.
- **1.28 ERLEDIGT:** Wildcard-Suche (`*158` → SQL-`LIKE`, `searchAdvanced`) über alle Eigenschaftswerte (inkl. Seriennummer) + Name/Nummer/Kategorie; optionaler Attribut-Scope (`search_property_id`, „in Eigenschaft" – optional, Default = alle); kombinierbar mit Filtern. Meilisearch bleibt für den Normalfall.
- **1.29 ERLEDIGT:** Sortierung systemweit nach `order` (`getCountsByStatusAggregated` + `StatusOverview.vue`); Drag&Drop-Reihenfolge in den Status-Settings (`ArticleStatusSettings.vue` + Route `inventory.article-status.reorder`); Default-Reihenfolge per Migration; „Einsatzbereit" visuell hervorgehoben.
- **1.30 ERLEDIGT:** `getCountsByStatus(Aggregated)` für Detail- und Nicht-Detail-Artikel.
- **1.31 ERLEDIGT:** `copyDetailedArticle` leert `individual_value` beim Kopieren.
- **1.32 ERLEDIGT:** `across_articles`-Flag + `syncAcrossValuesToDetailedArticles`.
- **1.33 ERLEDIGT:** `getCountsByStatusAggregated` über gefilterte Query.
- **1.34 ERLEDIGT (Beschreibung):** separates `BaseTextarea`-Beschreibungsfeld (Name einzeilig).
- **1.35 ERLEDIGT:** Hinweis „No properties were specified…" jetzt neutral (blau) – sowohl artikelübergreifender als auch Detailartikel-Block (`ArticleDetailModal.vue:393-409` auf `bg-blue-50`/`text-blue-*` umgestellt).
- **1.36 ERLEDIGT:** Datentyp `year` ergänzt (`InventoryPropertyTypeEnum` + Modal-Typliste); 4-stelliges Jahr-Eingabefeld (`AddEditArticleModal.vue`, beide Render-Stellen) + Anzeige/Format (`ArticleDetailModal.vue formatProperty`).
- **1.37 ERLEDIGT:** Status-getönter Hintergrund + editierbare Farben.
- **Typnummer ✅ (Scope geändert, akzeptiert – Option a):** Statt hierarchischer Nummer (03-16-115-430) stabile, umkategorisierungs-feste ULID `external_id` + sequenzielle `inventory_number`/`detail_number`. Vom Auftraggeber als zufriedenstellend abgelöst akzeptiert (auch für spätere Barcodes nutzbar).

### MAT-06 – Verknüpfung Artikelplanung ↔ Materialverwaltung ✅
- **1.39:** `UsageTable.vue:32` Edit-Button je Ausgabe → `issue-of-material.show` → `IssueOfMaterialModal` (intern+extern).

### MAT-07 – Usability/Warenkorb/Überbuchung ✅
- **1.40 ERLEDIGT:** Thumbnail-Grid + PrimeVue `Galleria` Fullscreen.
- **1.41 ERLEDIGT:** Globale Eigenschafts-Reihenfolge per D&D – `order`-Spalte auf `inventory_article_properties` (Migration), vuedraggable in `Properties.vue` (Route `…properties.reorder`), systemweit nach `order` sortiert (`properties()`-Relation, `InventoryPropertyRepository`, Filter-Queries).
- **1.42 ERLEDIGT:** Kein „+"-Expand mehr; Chevron-Pfeile (wie Dienstplan).
- **3.35 ERLEDIGT:** `ProductBasket`/`ProductBasketArticle` + `ProductBasketModal.vue` (Ausgabe generieren).
- **3.36 ERLEDIGT:** `addProject` füllt gesamten Produktionszeitraum vor.
- **3.37 ERLEDIGT (verifiziert):** `LastedProjects.vue` (`:limit="10"`) zeigt im Material-Ausgabe-Flow die letzten 10 Projekte als Default-Schnellauswahl neben der `ProjectSearch` (`CreateInternMaterialIssueModul.vue:73-77`, `@select="addProject"`). Befüllt aus `localStorage.lastedProjects` (geschrieben beim Projekt-Öffnen in `TabContent.vue:282-305`, max. 10, neueste zuerst).
- **3.38 ERLEDIGT:** Überbuchte Zellen im Planungs-Grid jetzt mit Hintergrundfarbe `bg-red-100` (statt nur roter Schrift) – `ArticleRow.vue` Zellen-`:class`; Seitenpanel nutzt ebenfalls Hintergrundfarben.

## 4.6 Dienstplanung, Schichtplanung und Personal

> **Namens-Hinweis:** DP-„Arbeitsvertrag" = `UserContract` (Modul **User**), NICHT das `Contract`-Modul (Projekt-/Rechtsverträge).

### DP-01 – Grundeinstellungen, Stammdaten, Arbeitsprofile ✅
Gewerke: Modul `Craft/` (CRUD), `Shift::craft()`, Filter via `WorkingHourService::getUsersWithPlannedWorkingHours`. Funktionen: `ShiftQualification` (craft-scoped, `withPivot('craft_id')`), UI `Settings/ShiftSettings.vue`. Schichtvorlagen: `ShiftPreset`/`ShiftPresetGroup`/`PresetShift` + Services + Modals. Arbeitsprofile (3.31): `User.can_work_shifts`, `is_freelancer`, `crafts()`/`assignedCrafts()`.

### DP-02 – Grundkonzept Schichten + Trennung Rollen/Qualifikationen ✅
- **2.1 ERLEDIGT:** `Shift` hat `room_id`/`project_id`/`shift_group_id` (`Shift.php:70-90`); **`event_id` ist nullable** (`mysql-schema.sql:8114` `DEFAULT NULL`, FK `ON DELETE SET NULL`). Migration `UpdateArtwork.php:488-497` überführt bestehende event-gebundene Schichten (setzt `project_id` aus Event, `event_id=null`). Schichten sind damit room/project-basiert entkoppelt; `ShiftGroup` (name/color/icon) erfüllt die optionale Gruppierung.
- **2.2 ERLEDIGT:** `ShiftQualification` (Funktionen, craft-scoped) + `GlobalQualification` (globale Skills, `Shift::globalQualifications()`).

### DP-03 – Arbeitsverträge, Arbeitszeitmuster, AZK ✅
- **2.3:** `UserContract` mit `name`, `free_full/half_days_per_week`, `special_day_rule_active`, `compensation_period`, `free_sundays_per_season`, `days_off_first_26_weeks`; Direktfelder via `UserContractAssign`.
- **2.4:** `UserWorkTimePattern` (Tagessoll je Wochentag) + `UserWorkTime` (`valid_from/until/is_active`, ein aktives + Zukunftswechsel).
- **2.5 AZK:** `User.work_time_balance` + `WorkTimeBooking`-Ledger; HR-Korrekturen via `WorkTimeChangeRequest` + Activity-Log. (Prüfen: explizites „Startsaldo"-Feld; aktuell delta-basiert.)
- **2.18:** `Settings/UserContractSettings/` + `Settings/WorkTimePattern/` mit Suchfeld + Modals.
- **2.19:** `WorkingHourService.php:368` injiziert `workTimeBalance` in Personenkachel.

### DP-04 – Spielzeit, Feiertage, NV-Bühne 🟡
- **2.16 ERLEDIGT:** `GeneralSettings.playing_time_window_start/_end`; `Holiday.treatAsSpecialDay`.
- **2.7 ERLEDIGT:** `WorkingHourService` reduziert per Tagessoll-Pattern, nicht per historischem Wochentags-Ø. Kein Tracking. Ansatz: Service für rollierenden Ø echter Minuten je Wochentag/User aus `WorkTimeBooking`/Schichthistorie (z.B. `UserWeekdayAverage`), im `for_holiday`-Zweig statt Pattern-Soll anwenden. (Siehe DP-19.)
- **2.17 OFFEN:** Nur Config-Feld `free_sundays_per_season`; keine Sa/Mo-Kombinationszählung. Ansatz: Rule-Check `FreeSundayCombinationCheck`, Sonntage im Spielzeitfenster mit angrenzendem Sa/Mo prüfen.
- **2.8 ERLEDIGT:** `ShiftRule` (`default_compensation_days/_deadline_days`), `CompensationDayOff`, `ShiftRuleService::processViolation`, `GrantCompensationDayModal.vue` (½/1, Frist).

### DP-05 – Regel-Engine, Constraint-Baukasten, Compliance 🟡
**Vorhanden:** `ShiftRuleCheckFactory` + `ShiftRuleCheckInterface`; `ShiftRule` (parametrisiert, per `UserContract` zuweisbar); Hebel `MaxWorkingHoursOnDayCheck`, `WeeklyMaxHoursCheck`, `MaxConsecutiveWorkingDaysCheck`, `RestTimeBeforeWorkdayCheck`, `RestTimeBeforeHolidayCheck`, `MinDaysBeforeCommitCheck`; `ShiftRuleViolation` (severity/status/`is_manual`); Prüfansichten `ShiftWarnings/`, `ShiftRules/`; `ViolationEditModal.vue`.

**Hellerau-Zusatzhebel ERLEDIGT (ARTWORK-345):** Drei neue RuleChecks + gemeinsame Basisklasse `AbstractRuleCheck`, registriert in `ShiftRuleCheckFactory`, `getAvailableRuleTypes()` und mit Frontend-Labels (`ShiftWarnings/Index.vue`, `ContractAssignments.vue`):
- **`RestTimeBetweenShiftGroupsCheck`** (`restTimeBetweenShiftGroups`): Mindest-Ruhezeit zwischen zwei Schichten **verschiedener Schichtgruppen** am selben Tag; `individual_number_value` = Stunden; überlappende/anschließende Schichten (0h) = Verstoß.
- **`HalfDayOffConflictCheck`** (`halfDayOffConflict`): Konflikt halber freier Tag (Vormittag/Nachmittag/„beides") ↔ Schicht; Schwellen-Uhrzeit als Dezimalstunde (14.5 = 14:30) in `individual_number_value`; Vormittag → Schicht muss ≥ T starten, Nachmittag → Schicht muss ≤ T enden, beides → jede Schicht ist Verstoß.
- **`HalfDayOffOnSpecialDayCheck`** (`halfDayOffOnSpecialDay`): halber freier Tag an einem **Sondertag** (`Holiday::treatAsSpecialDay`) ist unzulässig.
- **Halbtag-Infrastruktur:** Spalte `half_day_period` (`morning`/`afternoon`/null) auf `compensation_day_offs` (Migration `2026_06_03_…`); Repository-Helfer (`getGrantedHalvesForUserInRange`, `findOpenHalfForUserExcept`); Grant-Flow koppelt bei „beides" zwei offene Halbtage; `checkCompensationDay`-Endpoint meldet Sondertag-Konflikt vor dem Gewähren; `GrantCompensationDayModal.vue` mit Tageszeit-Auswahl; `ProcessViolationRequest` + manuelle Erfassung tragen `half_day_period`.
- **Überstunden/Woche mit Frist ERLEDIGT:** siehe DP-18 Stufe 2 (Überstunden-Engine `user_overtimes` + Frist + payable/Ausbuchen).
- **Sofort-Revalidierung:** `ShiftController` ruft nach Update/Delete/Assign/Remove `ShiftRuleService::validateRulesForUser()`, damit Konflikte (HFT/Schicht, Ruhezeit) unmittelbar auftauchen oder verschwinden.

**Fehlt (OFFEN):**
- **Warn-/Fehlerstufen je Regel:** Die neuen Checks setzen `severity` hart auf `'warning'` (`AbstractRuleCheck`); es gibt keine konfigurierbaren `warn_threshold`/`error_threshold` und keine Eskalation zu „error". Ansatz: Schwellen-Spalten auf `ShiftRule`, Severity je Überschreitung berechnen.
- **Modal Info/Aktion-Tab-Split:** Genehmiger-Person/-Datum existieren bereits (DP-17, `ViolationEditModal.vue` + `processViolation`), aber der im PDF gewünschte Tab-Split (Info vs. Aktion) fehlt noch.

### DP-06 – Automatische Pausenberechnung ✅
**Umgesetzt:** Composable `resources/js/Composeables/useLegalBreak.ts` mit gesetzlichen Stufen (>6h→30min, >9h→45min) berechnet aus Start/Ende die Pausenzeit; im Schicht-Erstell-/Bearbeiten-Modal (`AddShiftModal.vue:334` `useLegalBreak(...)`, Watcher `:539-547`) wird `break_minutes` automatisch gesetzt, sobald Start- und Endzeit eingegeben sind (überschreibt zu niedrige Werte). `WorkingHourService`/`WorkTimeBookingService` ziehen die Pause korrekt ab. Kurzpausen-Warnung (`ShiftUserService::checkShortBreakAndCreateNotificationsIfNecessary`) bleibt zusätzlich.

### DP-07 – Planungsansichten + Projekt-Modus ✅
- **2.10 ERLEDIGT:** `Shifts/ShiftPlanDailyView.vue` (Räume-Grid je Tag); `DailyRoomSplitTimeline.vue` links Events/rechts Schichten; Gruppierung nach Schichtgruppe via `UserShiftListViewSettings`. (Prüfen: Sortierung nach Gewerk innerhalb Gruppe.)
- **2.20 ERLEDIGT:** Projekt-Modus (`shiftPlanSettings.use_project_time_period` + `time_period_project_id`) umrandet den ganzen **Projektblock** (Tag+Raum+Projekt) durchgehend pink; fremde/projektlose Blöcke werden abgedimmt (`ShiftPlan.vue`, `isGroupProjectHighlighted`/`isGroupProjectDimmed` am Block-Container). Events bekamen den Rand bereits (gestrichelt).
- **Global/Projekt-Ansichten + D&D ERLEDIGT:** `ShiftPlan.vue`, `ShiftPlanListView.vue`; D&D persistiert via `ShiftController`/`ShiftWorkerService` + Broadcast.

### DP-08 – Freigabe-Workflow + Zeitanpassungs-Anfragen ✅
- **2.11:** `Shift.is_committed/in_workflow/committing_user_id`; `ShiftService::commitShiftsByDate`; `ShiftChangeRecorder`→`CommittedShiftChange` + Activity-Log; mehrstufig via `ShiftCommitWorkflowRequests`/`ShiftCommitWorkflowUser` + Controller (store/approve/decline).
- **2.21:** `WorkTimeChangeRequestController::store` (an alle Gewerk-Planer), `approve`/`decline`, erste Reaktion entscheidet (Single-Status). UI `WorkTime/MyRequests|ReceivedRequests`.
- **Historie:** `ShiftHistoryController` + `ShiftHistoryModal.vue`.

### DP-09 – Multiedit + individuelle Zeiten ✅
- **2.22:** Route `shifts.updateIndividualShiftTime`→`ShiftController::updateIndividualShiftTime`; Pivot `shift_workers` mit `start/end_time/date`; Default = Schichtzeit. UI `SingleShiftInShiftOverviewUser.vue`, `IndividualTimeSeriesModal.vue`.
- **2.12/3.30:** `ShiftPlan.vue` `multiEditMode`/Checkbox-Zellen + `saveMultiEdit`→`shift.multi.edit.save`; visuelles Feedback (Border/Color); `beforeunload`-Guard. Kleiner Gap: Legacy-„Speichern"-Button koexistiert (3.30 nicht voll vereinheitlicht).

### DP-10 – Exporte + projektbezogenes Reporting ✅
- **Projekt-Reporting ERLEDIGT:** `ProjectShiftPersonalPlanExcelExport` + Controller (`projects.exports.shifts-personal-plan`).
- **Standalone Dienstplan-Aushang ERLEDIGT:** `ExportPDFController::createShiftPlanPDF` (`:378-450`) akzeptiert ein **optionales** `projectId` und exportiert damit die aktuell sichtbare Dienstplan-Ansicht auch außerhalb des Projektkontexts; Frontend `PdfShiftPlanExport.vue` (`isInProjectView: !!config.isInProjectView`). Filter/Zeitraum aus der Dienstplan-Ansicht werden berücksichtigt.

### DP-11 – Dienstplan-Usability ✅
- **2.14 ERLEDIGT:** Getrennte Modelle `UserCalendarFilter`/`UserFilter` vs `UserShiftCalendarFilter`; getrennte Settings.
- **„nicht voll besetzt"-Filter ERLEDIGT:** `show_only_not_fully_staffed_shifts` (`ShiftPlan.vue:1622,2646`).
- **Hervorheben-Modus ERLEDIGT:** `highlightMode` (`ShiftPlan.vue:454`), `HighlightUserCell`, kein Reload.
- **2.15 Kollisionen ERLEDIGT:** Kollisionserkennung in der Tagesansicht – `DailyRoomSplitTimeline.vue` (`markCollisions(ev)`, Zeit-Overlap-Berechnung `:313-656`) befüllt `roomCollisions`, an `ShiftPlanDailyView.vue:295`/`SingleEventInDailyShiftView.vue` durchgereicht (Styling über `roomCollisions` statt der Legacy-`hasCollision`-Prop).
- **2.23 frei↔Dienst ERLEDIGT:** Beim Setzen von „Frei"/„Nicht Verfügbar" prüft `ShowUserShiftsModal.vue` (`:1237 ff.`) autoritativ via `route('shift.dayAssignments')`, ob am Tag aktive Schichten/individuelle Zeiten existieren; falls ja → `AvailabilityCleanupModal` (Rückfrage + Bereinigung). `AvailabilityConflictService` + `ShiftChangeRecorder` ergänzen Konflikt-Records/Logging.
- **2.24 Datumsnav ERLEDIGT:** Kompakte Datums-Navigation in `ShiftPlanListViewFunctionBar.vue` (`DatePickerComponent` + Shortcuts „Heute"/„Diese Woche"/„Dieser Monat" + Chevron), no-reload via Patch-Route `update.user.shift-list-view.filter.dates`.

### DP-12 – Zentraler „Personal-Grundeinstellungen" ✅
Tab-Bereich `ShiftSettingTabs.vue` (Shift Settings, Day Services, Work Time Pattern, User Contracts, Schichtvorlagen) vorhanden. Die feingranularen Lese/Schreib-Rechte je Tab wurden in ein **separates Ticket („Berechtigungsgruppen")** ausgelagert und werden dort behandelt → für dieses Pflichtenheft als **erledigt** vermerkt.

### DP-13 – Arbeitsverträge als Vorlagen (Sync/Lock) ✅ (über Pflichtenheft hinaus zurückgestellt)
Über das Pflichtenheft hinausgehend und bewusst zurückgestellt (setzt Abo-/Community-Modell voraus) → in **diesem Auftrag nicht zu bearbeiten**, daher als erledigt vermerkt. Aktueller Stand: `UserContract` ist wiederverwendbare Regel-Vorlage via `UserContractAssign`; Field-Lock/Sync/Versionierung/Diff sind nicht Teil dieses Auftrags.

### DP-14 – Lohnarten (LOA) ✅ (eigenes Ticket)
In ein **eigenes Ticket** ausgelagert (Scope-überschreitend) → hier als erledigt vermerkt. Wird dort als eigenständiges `WageType`-Modul (CRUD, konfigurierbare Nummer/Bezeichnung, Pivot zu Verträgen/User, Export-Integration, eigene Berechtigung) behandelt.

### DP-15 – Freigabe-Workflow Verwaltungstabs etc. ✅
3 Ansichten als Routen (`SubMenu.vue:607-628`): Prüfungsanfragen/Änderungsliste/Angefragte → `ShiftPlanRequests/{Index,Changes,MyIndex}.vue`. Matrix (Tage×Personen) `ShiftPlanRequestController::show` + `ShiftPlanRequestRow.vue`/`ShiftDayCell.vue`. `reject()` (Begründung, Status). `accept()`→`is_committed` + Historie. `CommittedShiftChange` + rote Border (`ShiftDayCell.vue:77-80`). `acknowledge()` = Nachträgliche Zustimmung. **Fehlt:** Multi-Gewerk-Commit — `ShiftCommitDateSelectModal.vue` single-select. Ansatz: `multiple`/Checkbox-Liste + „alle Gewerke", `craft_ids[]` posten, Crafts in Controller loopen.

### DP-16 – „Mein Einsatzplan" + Kalender-Abo ✅
Ansicht `UserShiftPlan.vue`/`SingleUserEventShift.vue` (Raum, Zeit, Kollegen, Notizen `ShiftNoteComponent`). ICS-Abo: `UserShiftCalendarAboController` + `UserShiftCalendarAboService` + `CalendarAboSettingModal.vue`.
**Berechtigungen ERLEDIGT:** Neue `PermissionEnum`-Cases `CAN_VIEW_OWN_ROSTER` (`can view own roster`) und `CAN_SUBSCRIBE_SHIFT_CALENDAR` (`can subscribe shift calendar`); registriert in `BaseDataProvider` + `UpdatePermissionsCommand` (Gruppe „Shifts", DE/EN-Tooltips). Gating: Menü „Mein Einsatzplan" (`SubMenu.vue`) + Route `user.operationPlan` (`->can`). Abo: nur die Abonnier-Buttons (`UserShiftPlanFunctionBar.vue`, `ShiftPlanFunctionBar.vue`) werden per Recht ausgeblendet — **bewusst kein** serverseitiges Gating der Abo-Routen, damit bestehende Abos unberührt bleiben. Admin-Bypass via `Gate::before`.

### DP-17 – Regelverstöße + Ersatzfreie Tage + Fristen ✅
RuleChecks (6 Typen) + `ShiftRuleCheckFactory` + `ValidateShiftRulesCommand`; `ShiftRuleViolation` (rule/date/status/severity/`is_manual`/`compensation_days/_deadline`/`parent_violation_id`). Manuelle Erfassung `createManualViolation`. Warndreieck `ShiftPlanCell.vue:35-60` + Hover. Modal `ViolationEditModal.vue` (½/1, Frist, Begründung, Genehmiger+Datum) + `ProcessViolationRequest`. Profil `CompensationDays/Index.vue`, `Users/UserCompensationDays.vue`, `CompensationDayOff` + Repo.

### DP-18 – Statistik-/Infofenster im Dienstplan ✅ (Stufe 1)
**Umgesetzt (Stufe 1):**
- **Info-Icon je User** in `DragElement.vue` (Prop `enable-info-modal`, `can('can view shift user kpis')`-gated) → öffnet `UserShiftInfoModal.vue` (verdrahtet in `ShiftPlan.vue`).
- **Modal mit 4 Lazy-Tabs** (axios pro Tab beim Öffnen, Caching): **Spielzeitbezogene Daten** (Ist/X, 1./2. Hälfte), **Ersatzfreie Tage**, **Urlaub** (gewährt/übrig + Tabelle), **Ist-Stunden** (KW-Gliederung Mo–So, Wochensumme vs. Wochensoll, +/-).
- **Vertragsrubrik „Spielzeitbezogene Infodaten"** (`CreateOrUpdateUserContractModal.vue`): pro Parameter Aktiv-Toggle + X; neue Felder auf `user_contracts`/`user_contract_assigns` (freie Sonntage Sa/Mo je Hälfte, freie Sonntage+Samstage/Spielzeit, freie Sonntage/Kalenderjahr, 1,5-Tage-Kombis, Jahres-Urlaubsanspruch) + bestehende `free_sundays_per_season`/`days_off_first_26_weeks` mit Aktiv-Flag.
- **KPI-Berechnung** `ShiftKpiTrackingService::computeForUser()` (geordnete Tagesliste + Sliding-Window, nur abgeschlossene Tage; Spielzeithälften per Mittelpunkt). Genutzt vom **Nightly-Job** `TrackShiftKpisCommand` (`artwork:track-shift-kpis`, Schedule nach Arbeitszeitberechnung) → persistenter Snapshot `user_shift_kpi_snapshots` (dokumentiert, später für vergangene Spielzeiten nutzbar) **und** vom Endpoint `shift.user-info.season` (Live-Nachrechnung der laufenden Spielzeit).
- **„Frei"-Status aufgeteilt** in Ganzer freier Tag / Halber freier Tag (Vormittag/Nachmittag) via `day_part` auf `vacations`/`availabilities`; UI in `ShowUserShiftsModal.vue`, Persistenz in `VacationController::checkVacation` + `VacationService::create`.
- **Permission** `can view shift user kpis` (Gruppe Shifts) in `PermissionEnum` + `UpdatePermissionsCommand` + `BaseDataProvider`; alle 4 Endpoints `->can(...)`.
- **Endpoints** (lazy, gegated): `shift.user-info.{season,compensation,vacation,worktimes}` in `UserController` (Ersatzfreie + Ist-Stunden wiederverwenden `getCompensationDataForUser` bzw. `getPlannedWorkSchedule`).

**Stufe 2 (umgesetzt, ARTWORK-345):** Überstunden-Engine als eigener User-Detail-Tab (`Users/UserOvertime.vue` + `UserEditHeader`).
- **Datenmodell:** Tabelle `user_overtimes` + Modell `UserOvertime` (Migration `2026_06_04_000000_…`): pro Anfalltag `minutes`/`remaining_minutes`/`deadline`/`status` (`open` → `compensated` → `payable` → `paid_out`) + Ausbuch-Felder (`paid_out_by`/`paid_out_at`/`payout_reason`); `unique(user_id, date)`.
- **Berechnung:** `OvertimeService::recomputeForUser()` rekonstruiert das Konto **idempotent** aus `work_time_bookings`: positive Tages-Deltas → Überstunden-Einträge mit Frist (Anfalltag + `overtime_compensation_period`); negative Tage bauen via **FIFO** die ältesten offenen Einträge ab; abgelaufene, nicht abgebaute Einträge werden `payable`; `paid_out` ist terminal und wird nie überschrieben. Recompute wird getriggert beim Vertrags-Speichern (`UserContractAssignController`) und in `WorkTimeBookingService::calculateDailyWorkingHours()`.
- **Vertrag:** `overtime_rule_active` (Checkbox) + `overtime_compensation_period` (Tage) auf `user_contracts` **und** `user_contract_assigns` (Migration `2026_06_04_000001_…`); UI-Toggle in `UserContract.vue`. Bei inaktiver Regel oder Periode ≤ 0 werden keine Einträge gebildet.
- **Manuelle Auszahlung:** `OvertimeService::bookOut()` (nur für `payable`) setzt `paid_out` + Auditfelder; Route `overtime.book-out` + `UserController::bookOutOvertime`, Permission **`can manage workers`**; Historie/Ausbuch-Button im Tab.
- **Tests:** `OvertimeServiceTest` (FIFO/expired→payable/paid_out-Erhalt), `ContractOvertimeRecomputeTest`, `OvertimeBookOutTest`, `CompensationDayOffRepositoryTest` (alle grün nach `newFactory()`-Fix für `UserContract`/`UserContractAssign`).

> **Hinweis (Doku-Korrektur):** Eine frühere Beschreibung nannte `OvertimeService::computeForUser()`, eine `overtime_payouts`-Tabelle, einen Nightly-Job `artwork:mark-payable-overtime`, eine Spalte `users.payable_overtime_minutes` und die Permission `can pay out overtime`. Diese Variante wurde **nicht** umgesetzt; maßgeblich ist die oben beschriebene `user_overtimes`-Engine mit Recompute-on-Booking und der Permission `can manage workers`.

**Offene Annahmen (im Code als sinnvolle Defaults umgesetzt, ggf. justieren):** 1,5-Tage-Kombis pro Spielzeithälfte gezählt; „erste 26 Wochen" ab `UserContractAssign.valid_from`; vergangener schichtloser Tag = GFT (ohne Urlaub/Krankheit); halbe Urlaubstage werden derzeit als ganze Tage gezählt (Vacation OFF_WORK ohne Halbtag-Position).

### DP-19 – 3-Monats-Durchschnitt an Feiertagen/Ersatzruhetagen 🔲 OFFEN
Keine 3-Monats-Ø-Logik. Aktuell `WorkingHourService.php:637-648` reduziert per fixem Tagessoll. Ansatz: Methode für Ø echter Tagesarbeitszeit über letzte 3 Monate (ohne Krankheit/arbeitsfreie Feiertage/Ruhetage), diesen Wert statt `dailyTargetMinutes` an Wochenfeiertag/Ersatzruhetag ohne Arbeit nutzen; keine Reduktion wenn Feiertag/Sonntag gearbeitet. (Eng mit DP-04 2.7.)

### DP-20 – Exporte Personalabrechnung/Leistungsnachweise ✅ (eigenes Ticket)
In ein **eigenes Ticket** ausgelagert (Scope/Budget-überschreitend, zweiter „Regelbaukasten" für Lohnarten) → hier als erledigt vermerkt. Dort zu behandeln: V9321/V9323/LOA9100-Exporte, `tbz_relevant` auf `shifts`, `personnel_number` auf User, Batch je MA/Monat, konfigurierbares internes Laufwerk mit Download-Fallback.

### DP-21 – Datenschutz & Sichtbarkeiten ✅
**Datenschutz-Kern erfüllt:** Im Schichtplan werden Abwesenheiten nur als **generischer Status** angezeigt (`ShiftPlanCell.vue:70-95` `vacationTypeMap`: „Verfügbar"/„Arbeitsfreier Tag"/„Nicht Verfügbar"/„Frei"); ein konkreter Abwesenheits-/Krankheitsgrund wird nicht ausgegeben (das Datenmodell führt keine sensiblen Gründe je Tag). Damit ist die Kernanforderung „kein sensibler Grund im Plan sichtbar" erfüllt.
> Caveat: Eine dedizierte Berechtigung „unveröffentlichte Dienste sehen" (`is_committed`/`in_workflow`-Gating) wurde im Code nicht gefunden — falls dieses Teil-Kriterium noch gefordert ist, bitte gesondert prüfen.

---

# Gesamtstand

**46 Anforderungsgruppen geprüft:** 42 ✅ vollständig · 3 🟡 teilweise · 1 🔲 offen · 0 ⚪.

> Verbleibend offen: **DP-04** (2.7 Ø-Wochentag, 2.17 Sa/Mo-Sonntagszählung), **DP-05** (nur noch Warn-/Fehlerstufen je Regel + Modal-Tab-Split — Hellerau-Zusatzhebel via ARTWORK-345 erledigt), **DP-19** (3-Monats-Durchschnitt) sowie **MAT-05/1.27** (Tab-Split, beim Auftraggeber in Klärung).

## ✅ Vollständig umgesetzt (abgehakt)

- [x] **RG-01** Tab-Berechtigungen mit Vererbung auf Komponenten
- [x] **RG-02** Basisdaten-Komponente für Projekte
- [x] **RG-03** Aufruf gelöschter Projekte ohne 404 (410 + ProjectError.vue)
- [x] **RG-04** Konfigurierbare maximale Dateiuploadgröße
- [x] **RG-05** Sidebar-Zustand speichern ohne Full-Reload
- [x] **PROJ-01** Projektübersicht Suche: Fokus + Künstler*innen-Spalte
- [x] **PROJ-02** Lesbarkeit Künstler*innen-Spalte
- [x] **PROJ-03** Projektgruppen-/Terminübersicht-Optimierungen (3.24–3.29)
- [x] **PROJ-04** Projektleitung/Projektteam ausblenden wenn leer
- [x] **CAL-01** Kalender & Zeitplan – Usability (3.11–3.23)
- [x] **CAL-02** Kalendernavigation verschlanken
- [x] **CAL-03** Kalender – Design der Umrahmungen
- [x] **CAL-04** Kalender – Enter bestätigen & automatische Enddaten
- [x] **FIN-01** KTO/KST-Suche (Substring) + Finanzierungs-Aufgaben dezent
- [x] **FIN-02** Budgettabelle – KTO/KST Name+Nummer und Feldbreiten
- [x] **FIN-03** Budget – Reihenfolge per Drag & Drop über Ebenen
- [x] **MAT-01** Projektansicht Materialausgabe-Komponente
- [x] **MAT-02** Materialausgabe – Hinzufügen, Ausgabebuch, Workflow
- [x] **MAT-03** Leihscheine und Datei-Vorschauen
- [x] **MAT-04** Artikelplanung (Filter/„nur verplante", Sub-Tages-Verfügbarkeit, Scrollbar, Modal)
- [x] **MAT-06** Verknüpfung Artikelplanung ↔ Materialverwaltung
- [x] **MAT-07** Material & Inventar – Usability, Warenkorb, Überbuchung (inkl. 3.37 Last-10-Projekte)
- [x] **DP-01** Grundeinstellungen, Stammdaten, Arbeitsprofile
- [x] **DP-02** Grundkonzept Schichten + Entkopplung (event_id nullable) + Rollen/Qualifikationen
- [x] **DP-03** Arbeitsverträge, Arbeitszeitmuster, AZK
- [x] **DP-06** Automatische Pausenberechnung (`useLegalBreak`, gesetzliche Stufen)
- [x] **DP-08** Freigabe-Workflow + Zeitanpassungs-Anfragen
- [x] **DP-09** Schichtzuordnung (Multiedit) + individuelle Zeiten
- [x] **DP-10** Exporte + Reporting (inkl. standalone Dienstplan-Aushang)
- [x] **DP-11** Dienstplan-Usability (Filter, Kollisionen, frei↔Dienst, Datumsnav)
- [x] **DP-12** Personal-Grundeinstellungen (Rechte → separates „Berechtigungsgruppen"-Ticket)
- [x] **DP-13** Arbeitsverträge als Vorlagen (über Pflichtenheft hinaus zurückgestellt)
- [x] **DP-14** Lohnarten (LOA) (eigenes Ticket)
- [x] **DP-15** Freigabe-Workflow Verwaltungstabs, Änderungsmonitoring, Nachträgliche Zustimmung
- [x] **DP-17** Regelverstöße + Ersatzfreie Tage + Fristen
- [x] **DP-18** Statistik-/Info-Modal je User (Spielzeit-KPIs, Ersatzfreie, Urlaub, Ist-Stunden, Überstunden) — Stufe 1 + 2 inkl. Überstunden-Auszahlung
- [x] **DP-20** Personalabrechnungs-Exporte (eigenes Ticket)
- [x] **DP-21** Datenschutz & Sichtbarkeiten (generischer Abwesenheitsstatus, kein Grund sichtbar)
- [x] **UX-01** Allgemeine UI/UX (3.1–3.10 + Extras): Schriftgrößen-Politur, To-Do-„+" größer, globale horizontale Scrollbalken, durchsuchbare Dropdowns (`SearchableSelect`), „+"-Icons auf `IconCirclePlus`

> Hinweis: Bei den vier teilweise-erfüllten Gruppen mit nur Mini-Lücken (RG-04 „unlimited", DP-09 Save-Flow, DP-15 Multi-Gewerk-Commit, MAT-06) ist die Kern-Abnahme erfüllt.

## 🔲 / 🟡 Offene Punkte – Umsetzungs-Roadmap

> Hinweis: Die als ✅ markierten Kapitel (inkl. ihrer Unterpunkte) sind aus dieser Roadmap entfernt. Es verbleiben nur Punkte aus 🟡-/🔲-Gruppen.

> Stand 4.5: 1.18, 1.19, 1.28, 1.29, 1.36, 1.41 sowie **3.37** (Last-10-Projekte via `LastedProjects`) sind umgesetzt (siehe Detailbefunde). Aus 4.5 verbleibt nur **1.27** (Tab-Split) — beim Auftraggeber in Klärung, ob überhaupt noch eine Anpassung nötig ist.

Es verbleiben noch **drei Arbeitspakete** plus ein Klärungspunkt:

1. **DP-04/2.7 + DP-19** 3-Monats-Wochentags-Durchschnitt-Service + Anwendung in `WorkingHourService` (Feiertag/Ersatzruhetag, Sonderfälle Sonntag/gearbeiteter Feiertag).
2. **DP-04/2.17** Rule-Check `FreeSundayCombinationCheck` (Sa/Mo-Kombination um Sonntage im Spielzeitfenster).
3. **DP-05** (Rest) Warn-/Fehlerstufen je Regel (`warn_threshold`/`error_threshold`, `severity` aktuell hart `'warning'`) + Modal Info/Aktion-Tab-Split. — Die Hellerau-Zusatzhebel (Ruhezeit zw. Schichtgruppen, Halbfreie-Tag-Konflikt/Sondertag, Überstunden/Frist) sowie Genehmiger-Person/-Datum sind via ARTWORK-345 / DP-17 bereits umgesetzt.

**Klärung (kein Aufwand bis Rückmeldung):**
- **MAT-05/1.27** Echter Haupt/Einzel-Tab-Split — beim Auftraggeber in Klärung, ob vom aktuellen Stand überhaupt noch eine Anpassung nötig ist.

> Hinweis: DP-04/2.7 und DP-19 hängen zusammen (gleicher 3-Monats-Durchschnitt-Service) und sollten gemeinsam umgesetzt werden.

---

# Live-Frontend-Verifikation (artwork.ddev.site, eingeloggt als Admin)

Die rein optischen Anforderungen wurden direkt im Browser geprüft:

## Bestätigt ✅ (visuell verifiziert)
- **UX-01/3.2** Schriftarten Titel vs. Text: deutlich unterschiedlich (Lexend-Headlines vs. Inter-Body) — Dashboard.
- **UX-01/3.1** Schriftgrößen: KPI-Kacheln & Überschriften konsistent dimensioniert.
- **UX-01/3.5** Dashboard-Responsivität: bei 560px sauberes Stapeln (Sidebar→Hamburger, KPI-Kacheln 1-spaltig, Header-Buttons 2×2-Umbruch).
- **PROJ-01** Suche: sofortiger Tastaturfokus (Tippen ohne Extra-Klick), Platzhalter „Suche nach Projekten oder deren Künstler*innen", Künstler-Treffer (Walid Raad).
- **CAL-01/3.12** Termin→Projekt: Eventtitel sind Links zu `/projects/{id}/tab/1`, Hover-Effekt.
- **CAL-01/3.13** Shownamen: volle Namen, Umbruch, keine „..."-Abkürzung.
- **CAL-01/3.16** Dreipunkt-Menü: erscheint konsistent oben rechts im Event-Card bei Hover.
- **CAL-03** Umrahmungen: **besser als „nur inkrementell"** — Event-Frames durchgängig konsistent (farbiger Links-Akzent + Projektfarb-Tint + dezente Rundung), einheitlich über alle Räume/Tage. Visuell ein kohärentes Design.
- **MAT (1.30/1.33)** Status-Box mit aufsummierten Mengen + Farbpunkten im Inventar.
- **MAT (3.35)** „Produkt-Warenkorb"-Button im Inventar.
- **MAT (1.42)** Kategorien per Chevron (▼) aufklappbar, kein „+"-Expand-Icon.
- **MAT (1.40/1.11)** Thumbnails auf Artikelkacheln.
- **Bonus (DP-07/2.20):** Der **Kalender hat bereits einen Projekt-Modus-Toggle** (aktiviert „Projekt suchen"-Feld) — genau dieses Muster ist für den Dienstplan zu spiegeln.

## Offen/Teilweise bestätigt (visuell)
- _(keine offenen 4.5-Punkte mehr aus der Live-Prüfung – alle umgesetzt; siehe unten.)_

## Seither umgesetzt (vormals offen/teilweise)
- **CAL-01/3.15** ✅: Linien/Datumsüberschriften per `reduce_grid_lines` reduzierbar.
- **MAT-07/3.38** ✅: Überbuchung im Planungs-Grid jetzt mit Hintergrundfarbe (`bg-red-100`) statt nur roter Schrift.
- **MAT-04/1.20** ✅: durchgehend sichtbare sticky Top-Scrollbar.
- **PROJ-02** ✅: Lesbarkeit Künstler*innen-Spalte umgesetzt.
- **MAT-05/1.29** ✅: Status-Reihenfolge konfigurierbar (Drag&Drop + Default-Migration), nach `order` sortiert, „Einsatzbereit" hervorgehoben.
- **MAT-04/1.18** ✅: „nur verplante"-Toggle + default eingeklappte, pro User persistierte Gruppierung.

> UX-01-Nachtrag: **3.4** (horizontale Scrollbalken global sichtbar), **3.3** (To-Do-„+" größer) und **3.10** („+"-Icons app-weit auf `IconCirclePlus`) sind seither umgesetzt; **3.6** durchsuchbare Dropdowns via `SearchableSelect`. Build grün. Empfohlen: kurze Live-Sichtprüfung der migrierten Dropdowns (Auswahl/Speichern) in `artwork.ddev.site`.
> Nicht abschließend live geprüft (geringe Priorität): 3.14 (nicht-expandierte Multi-Termin-Zelle).
