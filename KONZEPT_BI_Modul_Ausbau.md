# Konzept: Grundlegender Ausbau des BI-Moduls

Stand: 2026-07-17 · Basis: dev (inkl. uncommitteter Stand vom 16./17.07.) · Vorarbeiten: `REVIEW_BI_Modul_2026-07-16.md`, `IDEEN_BI_Workflow.md`

> **Umsetzungsstand 17.07.2026: Phasen 1–5 sind vollständig umgesetzt** (uncommitted auf dev).
> 118 BI-Tests + volle Suite grün. Offen bleibt nur Phase 6 (Ausbaustufen, Abschnitt 8/10).
> Deployment-Hinweise: `artwork:update` ausführen (2 neue Migrationen, Kategorie- und Preset-Seeds)
> und sicherstellen, dass ein Queue-Worker die database-Queue verarbeitet (Exporte laufen jetzt asynchron);
> nach dem Deploy `php artisan queue:restart`.

Geklärte Grundsatzentscheidungen (17.07., Runde 1):
1. **Plan/Ist** = eigener, paralleler Plan-Datensatz (Scope-Dimension), Snapshots bleiben reine Einfrier-Punkte und können wahlweise Plan oder Ist einfrieren.
2. **Kategorien** = mandantenweit konfigurierbar, optional befüllbar; Gesamtwerte bleiben eigenständig erfassbar, Summenabgleich mit Warnung.
3. **Freier Zeitraumvergleich** in Dashboard **und** Projekt-Tab; YoY bleibt als Preset.
4. **Budget→BI** = Vorschlags-Buttons mit Quellenausweis, kein Automatismus (Auto-Sync bewusst nicht Teil dieses Konzepts).

Geklärte Produktfragen (17.07., Runde 2 — ehemals P1–P7 + Buchungsexport-Detaillierung):
5. **Buchungsexport-Inhalt** = Budget-Zeilen **plus** Sage-Ist: Blatt 1 eine Zeile je Budget-Zeile inkl. Sage-Ist-Summe, Blatt 2 die Sage-Einzelbuchungen (Details in Abschnitt 7).
6. **Buchungsexport-Zeitraum** = Projektzeitraum (Projekte mit Terminen im Zeitraum); Sage-Einzelbuchungen optional zusätzlich aufs Belegdatum eingrenzbar.
7. **KTO/KST-Filter** = Freitext, Matching „beginnt mit", Chips mit Spaltenwahl (Namen aus `budget_column_settings`, Live-Trefferzahl je Spalte).
8. **Leit-Auslastung** bleibt Ticketauslastung (verkauft/Kapazität); Platzauslastung (inkl. Freikarten) zusätzlich, wo Kategorien befüllt sind. *(P1)*
9. **Plan-Detail**: Gesamtwert als Standard, Pro-Termin-Modus optional. *(P2)*
10. **Erreichungs-Schwellen** zunächst fix (grün ≥ 100 %, gelb ≥ 80 %, rot darunter); ein eingefrorener Plan bleibt editierbar, der Snapshot ist der Nachweis. *(P3/P4, Default bestätigt)*
11. **Buchungsexport-Permission** = bestehendes `can export bi data` (kein neues Recht). *(P6)*
12. **Kategorien-Rollen**: die drei Rollen full/reduced/free reichen; Abo & Co. werden als Kategorie mit passender Rolle angelegt. *(P7)*
13. **Umsetzungsauftrag**: Phasen 1–5 werden vollständig umgesetzt; Phase 6 bleibt Ausbaustufe.

---

## 0. Ausgangslage (Kurzfassung)

- **Datenmodell:** `bi_project_data` (1 Zeile/Projekt; je Kennzahl `*_mode` total/per_event, `*_total`, `*_not_applicable`), `bi_event_data` (visitors/sold_tickets/revenue je Termin, unique project+event), `bi_snapshots` (JSON-Vollkopie in `data`), `bi_project_room_capacities`, `bi_time_efforts`, `bi_event_type_tags` (+Pivot), `bi_export_presets`. Custom Fields = `components` mit `is_bi_field` + `project_component_values`.
- **KPI-Single-Source:** `BiProjectMetricsService::summary()` (visitors inkl. ≈-Schätzung, sold_tickets, revenue, avg_price, capacity, occupancy, performances, event_days) — genutzt von Projekt-Tab, Dashboard, Export, Projektliste.
- **Dashboard:** `BiDashboardService` (Cache-Key mit `bi_dashboard_version`, 10-min-TTL) liefert kpis, comparable_kpis + previous_kpis (fest: −1 Jahr), monthly, data_gaps, by_category, Drilldown-Tabelle „Interne Steuerung" (mit Aufwand-Score). Dashboard-Export-Modal (17.07.) mit Kostenträger-Filter, Granularität projects/events/both, Spalten-Picker + Presets.
- **Budget/Sage:** KST/KTO existieren **nur** in `sage_assigned_data` / `sage_not_assigned_data` (`kst_stelle`, `kst_traeger`, `sa_kto`, `buchungsbetrag`, `belegdatum`, `buchungsdatum`, …); die Budget-Grid-Tabellen selbst haben keine Kontierungsfelder. `projects.cost_center_id` → `cost_centers` ist das artwork-eigene Kostenträger-Feld. Bestehender Zeitraum-Export: `BudgetsByBudgetDeadlineExport` (über `projects.budget_deadline`, ohne Kontierungsfilter).
- **Bekannte Baustellen** (Review 16.07., Abschnitt 5): keine serverseitige Autorisierung auf `projects/{project}/bi/*` und `settings/bi/*`, Export läuft `dispatchSync`, fast keine Tests, Presets faktisch immer global.

---

## 1. Baustein A — Besucher*innen-Kategorien (Vollzahler*innen, Ermäßigt, Freikarten, …)

### Ziel
„Besucher*innen" wird die Leitgröße mit optionaler Aufschlüsselung. Die getrennte Erfassung von Besucher*innen und verkauften Tickets bleibt erhalten (nicht jedes Haus schlüsselt auf), wird aber bei befüllten Kategorien automatisch hergeleitet und abgeglichen.

### Datenmodell
**Neu `bi_audience_categories`** (mandantenweit, Pflege in Settings → BI):
| Spalte | Typ | Bedeutung |
|---|---|---|
| `name` | string | Anzeigename („Vollzahler*innen", „Ermäßigt", „Freikarten", „Presse", …) |
| `pricing_type` | enum `full`/`reduced`/`free` | Rechenrolle — macht Quoten unabhängig vom frei wählbaren Namen berechenbar |
| `position` | unsignedInteger | Sortierung |
| `is_active` | boolean | Deaktivieren statt löschen (historische Werte bleiben lesbar) |
| SoftDeletes + timestamps | | Löschen nur, wenn keine Werte existieren; sonst deaktivieren |

Seed-Defaults (idempotenter Command im Muster `artwork:add-bi-event-type-tags`, eingehängt in `artwork:update`): Vollzahler*innen (`full`), Ermäßigt (`reduced`), Freikarten (`free`).

**Neu `bi_audience_category_values`:**
| Spalte | Typ | Bedeutung |
|---|---|---|
| `project_id` | FK | |
| `bi_audience_category_id` | FK | |
| `event_id` | FK nullable | `null` = Gesamtwert (TOTAL-Modus), sonst Pro-Termin-Wert |
| `scope` | enum `actual`/`plan` | vorbereitet für Baustein C |
| `quantity` | unsignedInteger nullable | |
| unique | (`project_id`,`bi_audience_category_id`,`event_id`,`scope`) | |

Der Modus der Kategoriewerte folgt **immer** `sold_tickets_mode` — kein eigener Modus-Schalter (sonst entstehen unaufsummierbare Mischzustände). Beim Moduswechsel werden Kategoriewerte wie heute die Kennzahlwerte genullt (bestehendes `switch*Mode`-Muster in `BiProjectDataService`).

### Rechenregeln (in `BiProjectMetricsService`)
- `tickets_issued` (ausgegebene Karten) = Σ aller Kategorien (full+reduced+free), wenn Kategorien befüllt.
- `sold_tickets` = Σ der zahlenden Kategorien (full+reduced), wenn Kategorien befüllt; sonst wie heute Direktwert.
- `visitors` bleibt Direktwert (Besucher*innen ≠ Karten: No-Show, Überbesetzung). Die ≈-Schätzung fällt künftig bevorzugt auf `tickets_issued` zurück (Freikartengäste sind Besucher*innen!), erst dann auf `sold_tickets`.
- **Abgleich statt Zwang:** Sind sowohl Direktwert als auch Kategorien erfasst und weichen ab → gelber Hinweis-Chip („Kategoriesumme 812 ≠ erfasste 800") mit Ein-Klick-Übernahme der Summe. Kein stilles Überschreiben.

### Erfassung (UI)
- `BiAudienceRevenueSection`: unter Besucher*innen/Tickets ein aufklappbarer Block „Nach Kategorien aufschlüsseln" (nur aktive Kategorien, gleiche Total-/Per-Termin-Logik wie die Hauptkennzahl).
- `BiEventMetricsTable`: Kategorie-Spalten optional einblendbar (Spalten-Toggle im Tabellenkopf, Zustand pro User merken); Ausfüllhilfe/Bulk und Enter-Navigation funktionieren dort mit (bestehende `data-bi-cell`-Mechanik).
- Datenqualitäts-Banner: Kategorien sind **optional** und erzeugen keine „Lücke"; nur der Abgleich-Konflikt erscheint.
- Snapshots: `BiSnapshotService::create` nimmt `category_values` mit in `data` auf; `flatten()` ergänzt je Kategorie eine Zeile.

### Export
- Projektblatt: dynamische Spalten `audience_cat_{id}` (analog `tag_{id}`/`custom_field_{id}`) in `exportConfigurationOptions()`.
- Terminblatt (`eventColumnLabelMap`): Kategorie-Spalten fest hinter visitors/sold_tickets.

---

## 2. Baustein B — KPI-Ausbau (Quoten & Kennzahlen)

Alle neuen KPIs entstehen zentral in `BiProjectMetricsService::summary()` und fließen von dort in Projekt-Tab-Header, Dashboard-KPIs/-Tabelle und Export-Spalten (`columnLabelMap`). Null-Semantik wie bestehend: „nicht erfasst" = null, nie 0.

| KPI | Formel | Voraussetzung |
|---|---|---|
| **Freikartenquote** | free / tickets_issued | Kategorien |
| **Ermäßigungsquote** | reduced / (full+reduced) — Anteil an *verkauften* Karten | Kategorien |
| **Zahlendenquote** | (full+reduced) / tickets_issued (Komplement zur Freikartenquote, für Gremien oft die positivere Lesart) | Kategorien |
| **Ø-Erlös je verkaufter Karte** | revenue / sold_tickets — ersetzt das heutige `avg_price` begrifflich sauber (heute mischt es ggf. Freikarten nicht ein, Benennung schärfen) | — |
| **Ø-Erlös je Besucher*in** | revenue / visitors | — |
| **No-Show-Quote** | 1 − visitors / tickets_issued (nur wenn beides erfasst; negative Werte = Überbesetzung → als „–" mit Tooltip) | — |
| **Ø Besucher*innen je Vorstellung** | visitors / performances | — |
| **Umsatz je Veranstaltungstag** | revenue / event_days | — |
| **Plan-Erreichung** | ist / plan je Kennzahl (Baustein C) | Plan-Daten |

Begleitend zwei Konsistenz-Klärungen aus dem Review endlich umsetzen:
- „Auslastung" → **„Ticketauslastung"** benennen (rechnet mit verkauften Karten). Mit Kategorien zusätzlich **„Platzauslastung"** = tickets_issued / capacity anbieten — für Häuser mit hohem Freikartenanteil die ehrlichere Zahl. Produktfrage, welche im Dashboard die Leit-Auslastung ist (s. Abschnitt 9).
- `eventDays`-Definition vereinheitlichen (Starttage vs. volle Spanne, R-Befund).

Dashboard: Quoten als eigene KPI-Kachel-Reihe (einklappbar, nur wenn Kategorien im Zeitraum befüllt) + Spalten in der Steuerungstabelle (abwählbar). `avg_price` (QW17) kommt damit ohnehin an die Oberfläche.

---

## 3. Baustein C — Plan/Ist (Kern des Ausbaus)

### Datenmodell: Scope-Dimension statt Snapshot-Flag
- `bi_project_data`: Spalte **`scope`** enum `actual`/`plan`, Default `actual`; unique von (`project_id`) auf (`project_id`,`scope`). Die Produktions-Flags (`is_new_production` etc.) und `premiere_date` gelten scope-übergreifend → sie bleiben logisch am `actual`-Datensatz, die Plan-Zeile führt sie nicht (UI zeigt sie nur im Ist-Scope).
- `bi_event_data`: Spalte **`scope`**, unique auf (`project_id`,`event_id`,`scope`). Damit ist Plan pro Termin möglich (z. B. „Premiere 90 %, Folgevorstellungen 60 %"), aber nicht Pflicht — Plan nur als Gesamtwert ist der erwartete Normalfall.
- `bi_audience_category_values.scope` existiert ab Baustein A.
- `bi_snapshots`: Spalte **`scope`** enum `actual`/`plan` — ein Snapshot friert genau einen Scope ein. Bestehende Snapshots = `actual` (Migrations-Default).
- **Alle Lesepfade defaulten auf `actual`** (`BiProjectMetricsService`, `BiDashboardService`, Export, Projektlisten-Spalte `BI_KEY_FIGURES`, Print-Layout-Kopie) — dadurch ist die Migration verhaltensneutral und der Plan-Scope kann Feature für Feature angeschlossen werden.

### Services
- `BiProjectMetricsService::summary(Project, ?from, ?to, scope = actual)` — ein Parameter, keine Code-Duplikation. Neu: `planComparison(Project, ?from, ?to)` liefert je Kennzahl `{plan, actual, diff, attainment}` (attainment = ist/plan; für Quoten Differenz in Prozentpunkten).
- `BiProjectDataService`: alle Writes bekommen den Scope-Parameter; Cache-Bump (`bi_dashboard_version`) unverändert für beide Scopes.

### Erfassung (UI, Projekt-Tab)
- **Segment-Toggle „Ist | Plan"** im Kopf der BI-Komponente (nur sichtbar mit Edit-Recht oder wenn Plan-Daten existieren). Der Plan-Modus nutzt **dieselben** Sektionen/Tabellen (gleiche Komponenten, `scope`-Prop) — keine zweite Erfassungs-UI. Deutlich abgesetzte Optik (z. B. gestrichelter Rahmen + „Plan"-Badge), damit niemand versehentlich in den falschen Scope tippt.
- Plan anlegen: leerer Plan-Scope zeigt einen Einstieg „Planwerte erfassen" mit Schnellstart: (a) leer beginnen, (b) **„Ist-Struktur übernehmen"** (Modi + Kapazitäten kopieren, Werte leer), (c) **„Werte aus Projekt X kopieren"** (Vorgänger-Produktion als Vorlage — bei Wiederaufnahmen der Hauptfall).
- Snapshot-Sektion: beim Anlegen Scope wählbar; Empfehlung an Nutzer*innen im UI-Text: „Plan vor Vorstellungsbeginn einfrieren". (Der in der Klärung verworfene Auto-Freeze bleibt als optionale Ausbaustufe in Abschnitt 8.)

### Auswertung / Gegenüberstellung
- **Projekt-Tab:** Wenn Plan- und Ist-Werte existieren, zeigt der `BiKpiHeader` je Kachel zusätzlich „Plan 12.000 · 84 %" (Erreichungs-Chip grün/gelb/rot: ≥100 / ≥80 / <80 %, Schwellen zunächst hartkodiert). Neue Karte **„Plan-Ist-Verlauf"** (`BiChart`): X = Termine chronologisch, Balken = Ist kumuliert, Linie = Plan (kumuliert bei Pro-Termin-Plan, sonst Zielgerade auf den Gesamtwert), zweite Ansicht „je Termin" statt kumuliert. Kennzahl umschaltbar (Besucher*innen/Tickets/Umsatz). Darunter Delta-Tabelle je Kennzahl aus `planComparison()`.
- **Verlaufs-Chart über Snapshots** (bestehend) erhält Scope-Filter: Ist-Snapshots als Serie, Plan-Snapshots als horizontale Referenzlinien — so wird „Entwicklung gegen den Plan" über Kalenderzeit sichtbar. Voraussetzung für eine dichte Kurve sind regelmäßige Snapshots → Auto-Snapshots (Abschnitt 8) werden hier vom Nice-to-have zum Enabler.
- **Dashboard:** KPI-Kacheln bekommen (nur wenn Plan-Daten im Zeitraum existieren) eine dezente zweite Zeile „Plan: … · Erreichung … %". Steuerungstabelle: optionale Spalten `plan_visitors`, `plan_revenue`, `attainment` (sortierbar → „welche Produktion läuft dem Plan hinterher?"). `data_gaps`-Logik bleibt Ist-bezogen; ein separater, kleinerer Hinweis „n Projekte mit Terminen ohne Planwerte" ist optional zuschaltbar.

### Budget-Modul als Quelle (Vorschlags-Buttons)
- Neben dem Umsatz-Feld (Plan **und** Ist) erscheint, wenn das Projekt eine Budget-Tabelle hat, ein Vorschlags-Chip:
  - **Plan-Umsatz:** Summe der Einnahmen-Seite der Budget-Tabelle (Erlös-MainPositions) aus der kalkulierten Spalte.
  - **Ist-Umsatz:** Summe `sage_assigned_data.buchungsbetrag` der Erlös-Kette des Projekts (dieselbe Kette wie `getBookingCount`).
- Klick übernimmt den Wert ins Feld; gespeichert wird zusätzlich die Herkunft (`value_source` string nullable + `source_synced_at` auf `bi_project_data`, z. B. `budget_income`/`sage`) — UI zeigt „übernommen aus Budget am 17.07.", und wenn der Budget-Wert inzwischen abweicht, einen Aktualisieren-Hinweis. **Kein** Auto-Sync.
- Bewusste Grenze: Welche Budget-Positionen „Ticketumsatz" sind, ist ohne Konvention nicht ableitbar (Idee B1) — der Vorschlag nimmt die gesamte Einnahmen-/Erlösseite und sagt das dazu. Eine Positions-Markierung „zählt als Ticketumsatz" ist Ausbaustufe.

---

## 4. Baustein D — Freier Zeitraumvergleich (statt nur Vorjahr)

### Backend
- `BiDashboardService::getDashboardData(?from, ?to, ?compareFrom, ?compareTo)`; ohne Vergleichsangabe wie heute −1 Jahr (Verhalten stabil). `previous_kpis` → generalisiert zu `comparison`-Block `{range, kpis, comparable_kpis, monthly}`; die YoY-Sonderbehandlung (TOTAL-Ausschluss via `comparable_kpis`, `excluded_total_mode_projects`) gilt unverändert für jeden Vergleichszeitraum.
- Cache-Key um den Vergleichszeitraum erweitern.
- Monats-Chart bei frei gewählten Zeiträumen: Serien werden **auf Monats-Index normalisiert** (Monat 1..n ab Zeitraumbeginn) statt auf Kalendermonat gematcht — sonst sind Spielzeit (Aug–Jul) und Kalenderjahr nicht überlagerbar. Ungleich lange Zeiträume: kürzere Serie endet einfach (spanGaps-Verhalten wie gehabt), plus Hinweis-Chip „Zeiträume ungleich lang (10 vs. 12 Monate)".
- **Projekt-Tab:** `projects.bi.show` akzeptiert optional zwei Zeiträume; `metrics_summary` wird dann je Zeitraum gerechnet (nur sinnvoll für Per-Termin-Projekte — TOTAL-Kennzahlen mit demselben `comparable`-Mechanismus ausblenden).

### UI
- **Dashboard:** Zeitraumleiste wird zweizeilig: Zeitraum A (wie heute inkl. Presets Spielzeit/Kalenderjahr/12 Monate) + Vergleich B mit Presets **„Vorjahr" (Default)**, „Vorperiode" (gleiche Länge direkt davor), „Vorspielzeit", „Frei wählen" (von/bis, analog Datum-oder-KW-Muster aus dem Schichtplan-Zeitraum-PDF), „Kein Vergleich". Alle Delta-Chips/Charts beziehen sich auf B; Beschriftung überall konkret („vs. 01.08.24–31.07.25" statt „vs. Vorjahr").
- **Projekt-Tab:** in der KPI-Header-Karte ein Vergleichs-Popover mit denselben Presets; aktiv zeigt jede Kachel den B-Wert + Delta, die Termin-Charts beide Zeiträume. Haupt-Use-Case: Wiederaufnahme vs. Premierenserie desselben Projekts.
- N>2 Zeiträume (z. B. 5 Spielzeiten als Balkengruppen) bewusst **nicht** in diesem Schritt — die Datenstruktur (`comparison` als Objekt-Liste statt Einzelobjekt) wird aber so gebaut, dass es später ein reines Frontend-Thema ist.

---

## 5. Baustein E — Gendern

Bestandsaufnahme: Im BI-Modul sind exakt **3** de.json-Werte betroffen (`"Visitors": "Besucher"`, `"Total visitors": "Besucher gesamt"`, `"Visitors by category": "Besucher nach Sparte"`); alles andere BI-Sichtbare ist bereits neutral oder gegendert („Künstler*in / Gruppe", „Beteiligte Personen").

- Die 3 Werte → „Besucher*innen", „Besucher*innen gesamt", „Besucher*innen nach Sparte". Exporte/Blade ziehen dieselben Keys → automatisch mit erledigt; einmal gegenprüfen: Export-Blade-Hardcodes (Review S5) und Chart-Tooltips/Achsen in `Dashboard.vue`/`BiChart.vue`.
- **Verbindliche Konvention** für alles Neue aus diesem Konzept (Kategorien-Seeds „Vollzahler*innen", Plan-UI-Texte, Export-Spaltentitel): Gender-Stern, Sprachregelung wie gehabt „Projekt", nie „Produktion"… — als Notiz in die Projekt-Memory, damit es nicht pro PR neu diskutiert wird.
- Modulübergreifendes Gendern (144× „Nutzer", 25× „Mitarbeiter", 57× „Künstler" außerhalb BI) ist ein **eigenes Vorhaben** und bewusst nicht Teil dieses Konzepts (Aufwand + Abstimmungsbedarf, betrifft auch Benachrichtigungstexte/PDFs).

---

## 6. Baustein F — Export der Steuerungstabelle (Dashboard)

Der Dashboard-Excel-Export (17.07.) liefert bereits: Zeitraum, Kostenträger-Filter, Projekt-/Termin-Granularität, Spalten-Picker mit Presets. Was zur Anforderung „Interne-Steuerung-Export" noch fehlt:

1. **Steuerungs-Spalten exportierbar machen:** `effort_score`, `contracts_per_performance`, `bookings_per_performance`, `tasks_docs_per_production` (+ künftig `attainment`, Quoten aus Baustein B) in `columnLabelMap()` aufnehmen. Die Werte kommen aus `BiDerivedValuesService`/Score-Berechnung — Achtung: Score im Export mit denselben `score_weights` rechnen wie das Dashboard (eine gemeinsame Methode, kein Nachbau im Export-Service).
2. **Zweiter Einstiegspunkt direkt an der Tabelle** „Interne Steuerung": Export-Button im Kartenkopf öffnet dasselbe `BiDashboardExportModal`, aber **vorbelegt mit dem sichtbaren Zustand**: aktueller Zeitraum, aktiver Kategorie-Filter (→ Projektliste vorgefiltert), Spaltenauswahl = exakt die Tabellenspalten, Granularität `projects`. Damit ist „was ich sehe, bekomme ich als Excel" ein Klick, und der Power-Fall (Spalten ändern, Termine dazu) bleibt im selben Modal.
3. **Preset „Steuerungssicht"** mitliefern (Seed-Preset, `is_shared`), damit der Spaltensatz auch aus dem Projekt-Export-Modal abrufbar ist.
4. Konsequenz aus wachsendem Umfang: Export **asynchron** stellen (`dispatch` statt `dispatchSync`, Review W6) — der Poll-Mechanismus existiert ja schon und ist heute faktisch tot. Voraussetzung: Worker für die `database`-Queue im Deployment (Runbook-Punkt).

---

## 7. Baustein G — Projektunabhängiger Budget-Export (KTO / KST / Kostenträger)

Neuer Export aus dem BI-Dashboard-Header („Budget-Export"). Grundgesamtheit sind **Budget-Zeilen** (SubPositionRows) aller Projekte mit Terminen im gewählten Zeitraum — angereichert um das Sage-Ist. „KTO" und „KST" meinen die **ersten beiden Budget-Spalten** (Position 0/1 jeder Budgettabelle): mandantenweit benannt über `budget_column_settings` (Seed: „KTO", „KST", „Position"; individuell umbenennbar), Werte stehen in den Zellen (`column_sub_position_row.value`).

### Modal
- **Zeitraum** von/bis (Pflicht; Default = Dashboard-Zeitraum). Bezug: **Projektzeitraum** — getroffen werden alle Projekte, die im Zeitraum mindestens einen Termin haben.
- **KTO/KST-Freitext-Filter:** Eingabefeld für Kontonummern/-präfixe. Beim Tippen (z. B. „123221") zeigt das System die individuell vergebenen Namen der beiden Spalten mit Live-Trefferzahl im gewählten Zeitraum („KTO: 14 Zeilen · KST: 0 Zeilen") — per Klick entsteht ein Chip „KTO beginnt mit 123221". Mehrere Chips möglich; Chips derselben Spalte sind ODER-, unterschiedliche Spalten UND-verknüpft. **Matching: „beginnt mit"** (Kontengruppen-Filter; exakt = volle Nummer eintippen). Options-/Trefferzahl-Endpoint serverseitig.
- **Kostenträger-Filter:** Multiselect über `cost_centers` (via `projects.cost_center_id`).
- Option „Sage-Einzelbuchungen zusätzlich aufs Belegdatum im Zeitraum eingrenzen" (Checkbox, Default aus): betrifft nur Blatt 2 und die Ist-Summen in Blatt 1; sonst zählt alles Ist des Projekts.
- `sage_not_assigned_data` bleibt außen vor (keine Projekt-/Zeilen-Zuordnung möglich — passt nicht zur Zeilen-Grundgesamtheit).

### Export (Excel, zwei Blätter)
- **Blatt 1 „Budgetzeilen":** eine Zeile je getroffene Budget-Zeile — Projekt, artwork-Kostenträger, Hauptposition, Unterposition, KTO-Wert, KST-Wert, Positionstext, dann die Wertspalten der Budgettabelle **ausgerichtet über den Spaltennamen** (gleichnamige Spalten teilen sich eine Excel-Spalte; Struktur-Referenz: `DetailedBudgetsByBudgetDeadlineExport`), zuletzt **Sage-Ist-Summe** der Zeile (Σ `buchungsbetrag` der an ihren Zellen hängenden `sage_assigned_data`).
- **Blatt 2 „Sage-Buchungen":** eine Zeile je Einzelbuchung unter den getroffenen Zeilen — Projekt, KTO/KST-Wert der Zeile, Belegdatum, Buchungsdatum, Belegnummer, Buchungstext, Betrag, Sachkonto (`sa_kto`), Sage-KST/-Kostenträger (`kst_stelle`/`kst_traeger`), Kreditor, Sammelbuchung ja/nein.
- Echte Zahlenformate (`WithColumnFormatting`), Summenzeile je Blatt, Filter + Zeitraum im Kopf und Dateinamen (Lehre aus QW18). Läuft über den asynchronen Export-Weg aus Baustein F.

### Technik & Sicherheit
- Neuer `BiBudgetExportService` + eigener Config-/Job-Weg über die bestehende Token/Status/Download-Mechanik (`BiExportService`-Muster wiederverwenden, nicht hineinquetschen — anderes Datenmodell, anderer Spaltensatz).
- Query als Join-Kette Table→MainPosition→SubPosition→SubPositionRow→Zellen (Spalten-Position 0/1 für den Filter, Wertspalten fürs Blatt) + `sage_assigned_data` je Zelle, **nicht** über N Projekt-Iterationen. Der KTO/KST-Filter läuft als `EXISTS` auf Zellen mit `columns.position IN (0,1)` und `value LIKE 'präfix%'`. Bei Bedarf Index auf `column_sub_position_row (column_id, value)`.
- Achtung Sage-Datumsfelder: `belegdatum`/`buchungsdatum` sind varchar — Format in Prod prüfen und beim Belegdatum-Filter konsistent parsen.
- **Berechtigung:** bestehendes `can export bi data` (Entscheidung 17.07.); Button im Dashboard nur mit dieser Permission (wie der KPI-Export).
- Abgrenzung: Der bestehende `BudgetsByBudgetDeadlineExport` (per Stichtag, projektaggregiert) bleibt unberührt — anderes Werkzeug für anderen Zweck.

---

## 8. Sinnvolle Ergänzungen (aus den Bausteinen abgeleitet)

Direkt einzahlend auf die neuen Features, jeweils klein gegenüber ihrem Nutzen:

- **E1. Serverseitige Autorisierung zuerst** (Review S1/S2): Mit Plan-Zahlen und Buchungsexport wächst die Sensibilität weiter — `projects/{project}/bi/*` und `settings/bi/*` bekommen vor allem anderen echte Gates (mindestens: Schreiben nur mit Projekt-Schreibrecht serverseitig geprüft; Settings nur Admin/Permission). Die bekannte modulübergreifende IDOR-Frage wird hier nicht gelöst, aber BI geht voran.
- **E2. Auto-Snapshots** (Idee A2): monatlicher Scheduler-Snapshot je aktivem Projekt, Scope `actual` — macht den Plan-Ist-Verlaufs-Chart (Baustein C) ohne manuelle Disziplin dicht. Kennzeichnung auto/manuell, Ausdünnung >24 Monate.
- **E3. CSV-Import** (Idee A4) um Kategorie-Spalten erweitern: Spalten-Mapping erlaubt neben Besucher*innen/Tickets/Umsatz auch die aktiven Kategorien — der Import ist der natürliche Weg, wie Kassendaten-Aufschlüsselung tatsächlich ins System kommt (manuell tippt das niemand pro Kategorie und Termin).
- **E4. Erinnerung nach Termin** (Idee A1) bleibt der wirksamste Hebel gegen Datenlücken — unverändert Kundenklärung nötig, im Konzept nur als Ausbaustufe geführt.
- **E5. Tests als Definition of Done je Phase:** Das Modul hat erst 2 Testklassen. Jede Phase liefert ihre Tests mit — prioritär `BiProjectMetricsService` (Scope, Kategorien-Summen, Quoten-Nullfälle, ≈-Fallback-Reihenfolge) als DB-lose Unit-Tests plus je ein Controller-Test pro neuem Endpoint (`ddev exec php artisan test`).
- **E6. Dashboard-PDF für Gremien** (Idee C): nach Baustein D deutlich wertvoller (Plan-Ist + Vergleich auf einer Seite); technisch Browser-Print-Route im bestehenden Print-Muster. Ausbaustufe.
- **E7. Kostenseite/Deckungsbeitrag** (Review I1): bleibt die größte inhaltliche Lücke *nach* diesem Ausbau. Baustein G legt dafür die Datengrundlage frei (Sage-Kette projektunabhängig abfragbar); Deckungsbeitrag je Projekt = Folgekonzept.

---

## 9. Produktfragen — alle entschieden (17.07.)

Die ehemals offenen Fragen P1–P7 sind beantwortet und oben in die Grundsatzentscheidungen (Punkte 5–13) eingearbeitet; die Bausteine spiegeln den entschiedenen Stand. Kurzfassung: Ticketauslastung bleibt Leitwert (P1) · Plan-Gesamtwert Standard, pro Termin optional (P2) · Schwellen fix 100/80 % (P3) · Plan nach Freeze editierbar, Snapshot = Nachweis (P4) · Buchungsexport über Projektzeitraum, Belegdatum nur als optionale Zusatz-Eingrenzung (P5, damit obsolet) · Permission = `can export bi data` (P6) · drei Kategorien-Rollen reichen (P7).

---

## 10. Umsetzungsphasen

Reihenfolge-Logik: erst Fundament (Sicherheit, Async, Gendern — billig, entsperrt alles Weitere), dann Kategorien **vor** Plan/Ist (die Scope-Spalte der Kategorietabelle entsteht gleich richtig), dann Vergleich, dann die Exporte, zuletzt Ausbaustufen.

**Phase 1 — Fundament** *(klein)*
Serverseitige Gates auf `projects/{project}/bi/*` + `settings/bi/*` (E1) · Export async + Worker-Runbook (F4/W6) · Gendern (Baustein E) · Test-Grundstock `BiProjectMetricsService` (E5).

**Phase 2 — Kategorien & KPIs** *(mittel)*
Tabellen `bi_audience_categories` + `bi_audience_category_values` (inkl. `scope`), Settings-Pflege-UI, Seeds · Erfassung in Sektion + Termin-Tabelle · Rechenregeln + Abgleich-Warnung · neue KPIs/Quoten in Service, Projekt-Tab, Dashboard (Ticketauslastung bleibt Leitwert, Platzauslastung zusätzlich) · Export-Spalten · Snapshot-Aufnahme.

**Phase 3 — Plan/Ist** *(groß, Kernstück)*
`scope`-Migrationen (project/event/snapshot-Daten) · Service-Scope-Parameter, `planComparison()` · Ist/Plan-Toggle + Plan-Schnellstart (Gesamtwert Standard, pro Termin optional) · Plan-Ist-Chart + Delta-Tabelle im Projekt-Tab · Snapshot-Scope + Verlaufs-Chart-Referenzlinien · Dashboard-Erreichungsspalten/-zeilen · Budget-Vorschlags-Buttons mit Herkunfts-Ausweis.

**Phase 4 — Freier Zeitraumvergleich** *(mittel)*
`comparison`-Generalisierung im Dashboard-Service (listenfähig gebaut) · Vergleichs-UI Dashboard (Presets + frei/KW) · Monats-Index-Normalisierung · Projekt-Tab-Vergleich.

**Phase 5 — Exporte** *(mittel)*
Steuerungs-Spalten in `columnLabelMap` + Score-Berechnung geteilt · Export-Button an der Steuerungstabelle mit Zustandsübernahme · Seed-Preset „Steuerungssicht" · Budget-Export (Baustein G: Service, Modal mit KTO/KST-Freitextfilter + Trefferzahl-Endpoint, zwei Blätter, ggf. Zell-Index).

**Phase 6 — Ausbaustufen** *(nach Bedarf/Kundenklärung)*
Auto-Snapshots (E2) · CSV-Import mit Kategorien (E3) · Termin-Erinnerungen (E4) · Dashboard-PDF (E6) · Kostenseite/Deckungsbeitrag als Folgekonzept (E7) · N-Zeiträume-Vergleich (D-Ausbau).

Jede Phase ist einzeln shipbar und lässt das Modul in konsistentem Zustand zurück; Phasen 2–5 sind untereinander nur durch die genannte Scope-Vorbereitung gekoppelt.
