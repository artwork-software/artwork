# Konzept: Konfigurierbarer Schichtplan-PDF-Export (Zeitraum-Überblick)

Stand: 2026-07-15 · umgesetzt

## Ziel & Abgrenzung

Ein PDF-Export des Schichtplans, der **nicht projektgebunden** ist und bewusst eine
Flughöhe **über** dem Tages-Export des Schichtentabs (`shiftplan_daily_project`,
zeitproportionale Tagesansicht) liegt: Er betrachtet einen **größeren Zeitraum**
(bis zu 6 Monate) und verzichtet auf Intra-Tages-Detail wie relative Zeitachsen.
Leitfrage des Dokuments: *Wer arbeitet wann, wo, in welchem Gewerk — und wo ist
noch etwas offen?*

Im Gegensatz zum bisherigen Stand (Export übernahm Zeitraum + Filter stumm aus der
aktuellen Ansicht) sind **Datumsgrenzen und Filter vor dem Export einstellbar** —
vorbelegt mit der aktuellen Schichtplan-Ansicht.

## Export-Dialog (`PdfShiftPlanExport.vue`) — Stand 2026-07-17

Erreichbar wie bisher über das Export-Icon in der Schichtplan-Funktionsleiste
(`ShiftPlanFunctionBar` → `ExportModal`, Tab „PDF Schichtplan Export"). Seit
2026-07-17 zusätzlich aus der allgemeinen Schichtplan-Tagesansicht
(`ShiftPlanDailyView`, Nicht-Projektmodus) über denselben Export-Button.

1. **Erläuterung** — Kopfzeile beschreibt den Export (projektungebunden,
   filterbar nach KW und Gewerk) im Muster der übrigen Export-Tabs.
2. **Überschrift** — frei editierbar (Default: Projektname bzw. „Schichtplan").
3. **Zeitraum** — wahlweise **nach Datum** (Start-/Enddatum) oder **nach
   Kalenderwoche** (Jahr + KW von–bis, ISO-Wochen; ergibt Montag der Start-KW
   bis Sonntag der End-KW; berechneter Zeitraum wird angezeigt). Vorbelegt mit
   der aktuellen Ansicht. Client-Validierung: Pflichtfelder, Ende ≥ Start,
   gültiger KW-Bereich; Export-Button ist bei ungültiger Eingabe deaktiviert.
4. **Gewerke-Filter** — Checkbox-Liste aller Gewerke, vorbelegt mit dem aktiven
   Ansicht-Filter (leer = alle vorausgewählt). „Alle auswählen/abwählen".
   Alle ausgewählt = keine Einschränkung (`craft_ids: []`).
5. **Modus** — „Räume nach Tagen" (Räume × Tage) oder „Personalübersicht"
   (Worker-Matrix, siehe eigenes Konzept); der Gewerke-Filter gilt für beide.
6. **Papier**: Größe (A3/A4/A5), Ausrichtung (Quer empfohlen), DPI.

Räume, Bereiche und Veranstaltungsarten werden weiterhin stumm aus der
aktuellen Ansicht übernommen (Info-Box im Dialog weist darauf hin).

## Backend (`ExportPDFController::createShiftPlanPDF`, Route `shift.plan.export.pdf`)

- **Gewerke-Override** (`craft_ids`): falls im Request vorhanden, **nur
  in-memory** auf das UserFilter-Objekt gesetzt; der gespeicherte
  Schichtplan-Filter des Users bleibt unverändert. Leeres Array = keine
  Gewerke-Einschränkung. Übrige Filterdimensionen kommen weiter aus der Ansicht.
- **Filter-Ausweis im PDF-Kopf**: Zeitraum inkl. `kwRange` („KW 29/2026" bzw.
  „KW 29/2026 – KW 33/2026") sowie aktive Filter (Räume, Veranstaltungsarten,
  Gewerke) — ein gefilterter Export ist damit als solcher erkennbar. Gleiches
  gilt für die Worker-Matrix (`WorkerShiftPlanPdfExportController`:
  `kwRange` + `craftFilterNames` im Header von `shiftplan_worker_matrix`).
- Zeitraum aus Request; `end < start` → auf `start` gesetzt; Kappung auf 183 Tage
  (Spiegel des Ansicht-Limits).
- Tests: `tests/Feature/Http/Controllers/ShiftPlanPdfExportTest.php`
  (Override in-memory, KW-Ausweis, gespeicherter Filter unangetastet).

## Export-Konsolidierung (Stand 2026-07-17)

Alle Schichtplan-Exporte hängen an **einem** Export-Button mit geteiltem
Tab-Modal (`ExportModal`); jeder Tab erklärt kurz, was exportiert wird:

- **Schichtplan (Wochenansicht)**: PDF Schichtplan (Räume×Tage / Personalübersicht),
  Excel Arbeitszeit-Übersicht, Excel Gewerk-Verteilung (permission-gated).
- **Schichtplan (Tagesansicht, ohne Projekt)**: dieselben Tabs (neu, vorher gar
  kein Export-Zugang in der Tagesansicht).
- **Projekt-Schichtentab** (`ShiftPlanDailyView` im Projektmodus): Tabs
  „PDF Tagesplan (Projekt)" (`PdfDailyProjectShiftPlanExport.vue`) und
  „Excel Personalplan (Projekt)" (`ExcelShiftPersonnelPlanExport.vue`) —
  ersetzen die früheren Einzel-Buttons/Modals
  (`ExportDailyProjectShiftPlanModal.vue`/`ExportShiftPersonnelPlanXlsxModal.vue`
  wurden entfernt).
- `hideEmptyRooms`: filtert Räume ohne Zellinhalt nach Aufbau des `roomLookup`.
- Datenpfad unverändert: `getFilteredRooms` → `filterRoomsEventsAndShifts` →
  `mapRoomsToContentForCalendar` (identisch zur Ansicht, daher 1:1-Konsistenz).

## PDF-Layout (`pdf/shiftplan_export.blade.php`)

- **Eine ISO-Kalenderwoche pro Seite**, Grid Räume (Zeilen) × Wochentage (Spalten),
  Wochenende getönt.
- Kopf je Seite: Titel, Zeitraum, aktive Filter (Räume/Bereiche/
  Veranstaltungsarten/Gewerke) als Klartext, „Leere Räume ausblenden"-Hinweis,
  KW-Nummer, Ersteller + Erstellzeitpunkt.
- **Termin-Kachel** = eine Zeile: Zeit (bzw. „Ganztägig") + Name, linker Farbbalken
  in Veranstaltungsart-Farbe.
- **Schicht-Kachel** = Kopfzeile (Gewerk-Kürzel, Zeit, Pause, Besetzungs-Badge
  `x/y` grün=voll/rot=offen, grüner Punkt=festgeschrieben) + Beschreibung kursiv
  + **Mitarbeitende als Fließtext** („Anna Muster, Ben Beispiel, 2× Offen").
  Bewusst KEINE Zeile pro Person: wkhtmltopdf kann Tabellenzeilen nicht über
  Seiten umbrechen — hohe Zellen führten sonst zu einer Raumzeile pro Seite
  (23 → 10 Seiten bei 3 Wochen Kampnagel-Seed).
- Projektmodus (Zeitraum eines Projekts aktiv): fremde Kacheln gedimmt, Projekt-
  Kacheln pink umrandet + Legende (unverändert aus Vorversion).

## Fallen

- **wkhtmltopdf bricht `<tr>` nie über Seiten** — Kachelinhalte müssen kompakt
  bleiben; keine per-Zeile-Layouts für Worker einführen.
- Overrides via `$request->exists()`: leeres Array = Dimension löschen,
  fehlender Key = gespeicherten Filterwert behalten.
- `Inertia::location` liefert im Test ohne `X-Inertia`-Header **302** (nicht 409).
- Tests mocken `Barryvdh\Snappy\PdfWrapper` im Container
  (`tests/Feature/Http/Controllers/ExportPDFControllerTest.php`) und prüfen die
  View-Daten — kein wkhtmltopdf-Binary nötig.
