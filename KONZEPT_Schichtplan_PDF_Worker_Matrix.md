# Konzept: Schichtplan-PDF als Mitarbeitende-Tage-Matrix

Stand: 2026-07-15

## Ergebnis der Bestandsaufnahme

Der gewünschte PDF-Export existiert noch nicht vollständig.

Vorhanden sind:

- der allgemeine Schichtplan-PDF-Export als **Räume x Tage**-Matrix
  (`ExportPDFController::createShiftPlanPDF`, `pdf/shiftplan_export.blade.php`),
- der persönliche Monats-PDF für **eine einzelne Person**
  (`ExportPDFController::createUserShiftPlanPDF`, `pdf/user_shift_plan.blade.php`),
- der Projekt-Tagesexport mit tagesbezogener Detaildarstellung,
- das gewünschte Raster bereits in der Schichtplan-Oberfläche: **Tage als Spalten,
  Mitarbeitende als Zeilen**. Dort werden Schichten mit worker-spezifischen
  Pivot-Zeiten, individuelle Zeiten und Tagesdienste bereits geladen.

Keiner der vorhandenen PDFs kombiniert mehrere Mitarbeitende als Zeilen mit
Tagen als Spalten, Raum und persönlicher Einsatzzeit in der Zelle sowie
Tagesdiensten.

## Zielbild

Ein zusätzlicher Exportmodus **„Personalübersicht“** im bestehenden
Schichtplan-Exportdialog:

| Mitarbeitende | Mo 13.07. | Di 14.07. | Mi 15.07. | ... |
|---|---|---|---|---|
| Anna Muster | 08:00-14:00 · Halle 1<br>Tagesdienst: Kasse | 10:00-18:00 · Studio | Frei | ... |
| Ben Beispiel | Individuelle Zeit 09:00-12:00 | 18:00-23:30 · Saal | Tagesdienst: Sicherheit | ... |

Pro Zelle werden kompakt und in dieser Reihenfolge angezeigt:

1. **Schichteinsätze** mit der persönlichen Start-/Endzeit aus dem
   Worker-Pivot (`shift_workers.start_time` / `end_time`), nicht nur mit der
   allgemeinen Schichtzeit; dazu Raum, optional Gewerk/Funktion und ein Status
   für festgeschriebene bzw. noch angefragte Schichten.
2. **Individuelle Zeiten** mit Titel und Zeit beziehungsweise „ganztägig“.
3. **Tagesdienste** als farbiger, ausgeschriebener Eintrag. Icons können
   ergänzend erscheinen, der Name muss im Ausdruck auch ohne Icon verständlich
   bleiben.

Mehrere Einträge am selben Tag werden innerhalb der Zelle untereinander
angeordnet. Worker-spezifische Datumsgrenzen des Pivots sind bei mehrtägigen
Einsätzen ebenfalls maßgeblich.

## Umfang und Exportoptionen

Der neue Modus nutzt den Zeitraum und die Filter des bestehenden
Schichtplan-Exportdialogs. Ergänzt werden:

- Ansicht: `Räume x Tage` oder `Mitarbeitende x Tage`,
- Personentypen: interne Mitarbeitende, Freelancer, Dienstleister,
- standardmäßig nur Personen mit mindestens einer Schicht, individuellen Zeit
  oder einem Tagesdienst im Zeitraum,
- optional leere Personen anzeigen,
- optional nach Hauptgewerk/Funktion gruppieren, ohne eine Person mehrfach
  auszugeben,
- Schichten, individuelle Zeiten und Tagesdienste jeweils ein-/ausblendbar,
- Papierformat A3/A4 und Querformat; A3 quer ist die empfohlene Voreinstellung.

Der Zeitraum sollte für diesen Detailgrad auf maximal 31 Tage begrenzt werden.
Unabhängig davon wird immer wochenweise paginiert, damit sieben Tagesspalten
lesbar bleiben. Ein längerer Zeitraum kann später bewusst als eigener
kompakter Exportmodus ergänzt werden.

## Seiten- und Tabellenlayout

- Eine ISO-Kalenderwoche je horizontalem Seitenblock.
- Wiederholter Header mit Wochentag und Datum; Wochenenden und Feiertage werden
  dezent markiert.
- Die linke Namensspalte enthält Anzeigename, Personentyp und optional Gewerk.
- Jede Person erscheint pro Wochenblock genau einmal, auch wenn sie mehreren
  Gewerken zugeordnet ist. Das unterscheidet den PDF-Builder bewusst von der
  gruppierten UI-Darstellung, in der eine Person mehrfach vorkommen kann.
- Eine Worker-Zeile darf nicht über einen Seitenumbruch geteilt werden.
- Bei vielen Personen wird dieselbe Kalenderwoche auf Folgeseiten fortgesetzt;
  Datumsheader und Legende werden wiederholt.
- Die Worker werden serverseitig in passende Seiten-Chunks aufgeteilt. Das ist
  zuverlässiger als automatische Umbrüche von wkhtmltopdf, da dieses hohe
  Tabellenzeilen nicht sauber teilt.
- Eine kompakte Legende erklärt Schichtstatus, individuelle Zeit und
  Tagesdienst. Inhalte bleiben textlich verständlich und sind nicht allein von
  Farben abhängig.

## Technische Umsetzung

### 1. Route, Request und Berechtigung

- Neue POST-Route, zum Beispiel
  `shift.plan.export.worker-matrix.pdf`.
- Eigener invokable Controller statt weiterer Logik im bereits großen
  `ExportPDFController`, zum Beispiel
  `WorkerShiftPlanPdfExportController`.
- Eigener Form Request mit validiertem Zeitraum, Personentypen, IDs,
  Anzeigeoptionen, Papierformat und Orientierung.
- Dieselbe Berechtigung wie für die sichtbare Mitarbeitendenübersicht des
  Schichtplans. Private Kontaktdaten und Stundenkonten werden nicht exportiert.
- Der Dateiname bleibt ASCII-kompatibel; die Ausgabe verwendet den vorhandenen
  authentifizierten Downloadpfad.

### 2. Datenbeschaffung

Die vorhandene Worker-Pipeline wird wiederverwendet, aber nicht über einen
internen HTTP-Aufruf an `EventController::getShiftPlanWorkers`:

1. `WorkerService::getWorkersForShiftPlan(...)` lädt je Personentyp die
   Schichten einschließlich Pivot-Zeiten, Raum, Gewerk und Funktion.
2. `WorkerShiftPlanService::loadWorkerRelations(...)` ergänzt individuelle
   Zeiten im Zeitraum.
3. Die bereits zeitlich eingeschränkte `dayServices`-Relation liefert die
   Tagesdienste aus `day_serviceables.date`.
4. `WorkerShiftPlanService::buildWorkerData(...)` normalisiert die drei
   Personentypen.

Die gemeinsame Orchestrierung aus dem API-Controller sollte dabei in einen
kleinen, wiederverwendbaren Query-/Builder-Service extrahiert werden, zum
Beispiel `WorkerShiftPlanMatrixBuilder`. So verwenden UI und PDF langfristig
dieselben fachlichen Regeln, ohne Controller voneinander abhängig zu machen.

Der Builder erzeugt ein renderfertiges DTO:

```text
weeks[]
  weekNumber
  days[]: date, label, weekend, holiday
  workerPages[]
    workers[]
      id, type, displayName, craftLabel
      cells[YYYY-MM-DD]
        shifts[]: room, start, end, craft, function, status
        individualTimes[]: title, start, end, fullDay
        dayServices[]: name, color, icon
```

Die Blade-Datei darf keine Datenbankabfragen ausführen.

### 3. Zeitlogik

- Für einen Einsatz gelten zuerst die persönlichen Pivot-Werte
  (`start_date`, `end_date`, `start_time`, `end_time`).
- Fehlen persönliche Werte, wird auf die Schichtwerte zurückgefallen.
- Mehrtägige Einträge erscheinen an jedem betroffenen Kalendertag mit einer
  tagesbezogenen Darstellung, zum Beispiel `22:00-24:00`, `00:00-24:00` und
  `00:00-06:00`.
- Individuelle Zeiten nutzen ihre tatsächliche Datumsspanne und werden analog
  auf die betroffenen Tageszellen verteilt.
- Zeitzone ist `config('app.timezone')`; das PDF arbeitet nicht mit impliziter
  Browser-Zeitzone.

### 4. PDF-Erzeugung

- Neue Blade-Datei, zum Beispiel
  `resources/views/pdf/shiftplan_worker_matrix.blade.php`.
- Weiterverwendung des bestehenden Snappy/wkhtmltopdf-Stacks, damit keine neue
  Abhängigkeit und kein zweiter PDF-Renderpfad eingeführt wird.
- Serverseitige Seiten-Chunks und feste Spaltenbreiten statt komplexer
  CSS-Grid- oder JavaScript-Layouts.
- PDF zunächst in den bestehenden geschützten Speicher schreiben und danach
  über den vorhandenen Download-Endpunkt ausliefern.

## Tests

Mindestens folgende Pest-Featuretests sind erforderlich:

1. Gast beziehungsweise unberechtigte Person kann nicht exportieren.
2. Tage stehen in korrekter Reihenfolge und Worker erscheinen als Zeilen.
3. Die Zelle verwendet die persönliche Pivot-Zeit statt der allgemeinen
   Schichtzeit.
4. Raum, Gewerk/Funktion und mehrere Schichten pro Tag werden korrekt abgebildet.
5. Individuelle Zeiten, einschließlich ganztägig und mehrtägig, erscheinen am
   richtigen Tag.
6. Tagesdienste werden je Person und Datum ausgegeben.
7. User, Freelancer und Dienstleister werden korrekt normalisiert und gefiltert.
8. Leere Personen werden standardmäßig entfernt und optional beibehalten.
9. Wochen- und Worker-Paginierung wiederholt den Datumsheader korrekt.
10. Der PDF-Wrapper erhält View, Papierformat und Orientierung korrekt.

Zusätzlich sollte ein kleiner Builder-Unit-Test die schwierigen Grenzfälle um
Mitternacht und mehrtägige persönliche Pivot-Zeiten abdecken. Abschließend wird
eine erzeugte Test-PDF gerendert und visuell auf abgeschnittene Zellen,
Seitenumbrüche und Lesbarkeit geprüft.

## Umsetzungsschritte

1. Exportmodus und Optionen im bestehenden Dialog ergänzen.
2. Form Request, Route und Authorization anlegen.
3. Gemeinsamen Matrix-Builder aus der vorhandenen Worker-Datenpipeline bauen.
4. Render-DTO und Blade-Template umsetzen.
5. Feature- und Unit-Tests ergänzen.
6. A3/A4-Test-PDFs mit realistischen Datenmengen rendern und Seiten-Chunks
   kalibrieren.

## Abnahmekriterien

- X-Achse enthält für jede Seite maximal sieben Tage mit Datum.
- Y-Achse enthält die im Zeitraum relevanten Mitarbeitenden.
- Jede Person erscheint je Wochenblock höchstens einmal.
- Jede Schichtzelle zeigt mindestens persönliche Zeit und Raum.
- Worker-spezifische Zeiten haben Vorrang vor allgemeinen Schichtzeiten.
- Individuelle Zeiten und Tagesdienste sind pro Tag sichtbar.
- Keine Worker-Zeile wird zwischen zwei Seiten geteilt.
- Ein Zeitraum mit 50 Personen und sieben Tagen bleibt auf A3 quer lesbar und
  erzeugt reproduzierbare Folgeseiten.
- Der Export führt keine N+1-Abfragen aus und gibt keine privaten Kontaktdaten
  oder geschützten Stundeninformationen preis.
