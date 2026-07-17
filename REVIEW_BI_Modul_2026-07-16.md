# BI-Modul Review — 2026-07-16 (vor der Vorstellung am 17.07.)

> **Update 16.07. abends — umgesetzt:** R1 (TOTAL aus YoY ausgeschlossen, `comparable_kpis` + Hinweis),
> R2 (Kapazität zeitraumgefiltert), R3 (Delta in Prozentpunkten), R4 (Vorjahreslinie null/spanGaps),
> R6 (Excel-Zahlformate via `WithColumnFormatting`), R7 (Null-Guard), R8 (N+1-Fix + Vorjahres-Lauf nur KPIs),
> QW1/QW2 (zentraler Save-Indikator `BiSaveIndicator` + `useBiSaveFeedback`, Rollbacks), QW3 (Banner klickbar + grün),
> QW4 (Modal nur bei Datenverlust, konkret), QW5 (TextField blur-only), QW10 (de-DE-Formate), QW11 (Score-Gewichte
> im Payload + dynamischer Tooltip, Auslastungs-Tooltip), QW12 (Preset aktiv), QW15 (0 % → „–"),
> W1 (Draft-Puffer + Fetch-Sequenz-Guard gegen Race), W2 (neueste zuerst, Zukunft gedimmt, Enter-Navigation,
> Bulk-Teilfehler via allSettled). Verifiziert: Build grün, Backend-Smokes (Dashboard + Export) grün.
> R5 bleibt: Export läuft weiterhin synchron — vor der Demo mit realer Datenmenge testen.

Vier parallele Deep-Reviews (Projekt-Tab, Dashboard, Export/Settings, Backend/Kennzahlen).
Struktur: **1. Demo-Risiken** (vor morgen wissen/prüfen) → **2. Quick Wins** (heute machbar) →
**3. Workflows geradliniger** → **4. Inhaltlicher Ausbau** (Roadmap-Story) → **5. Substanz** (nicht demo-relevant, aber wichtig).
Zurückgestellte Ideen aus `IDEEN_BI_Workflow.md` sind unter 3./4. eingeordnet, wo sie zur Story passen.

---

## 1. Demo-Risiken — vor morgen wissen oder entschärfen

### R1. TOTAL-Modus ignoriert den Zeitraumfilter → Jahresvergleich wirkt kaputt ⚠️
`BiProjectMetricsService.php:104-151`: Im Modus TOTAL kommt immer `*_total` zurück, egal welches `from/to`.
Folgen im Dashboard (`BiDashboardService::aggregate`):
- Spielzeit-KPIs mischen datumsgefilterte Per-Termin-Projekte mit ungefilterten Gesamtwerten.
- **YoY-Vergleich für TOTAL-Projekte ist wertlos: Vorjahr == aktuelles Jahr** (derselbe Total). Fällt live sofort auf, wenn Deltas bei 0 % kleben.
→ Für morgen: Demo-Daten so wählen, dass die gezeigten Projekte im Per-Termin-Modus sind, oder den Punkt aktiv erklären („Gesamtwerte sind zeitraum-neutral"). Mittelfristig: TOTAL-Projekte aus YoY-Delta ausnehmen oder markieren.

### R2. Auslastung im Range-Modus systematisch zu niedrig
`BiProjectMetricsService.php:165-178`: `seatsCapacity` summiert die Kapazität **aller** Projekt-Events (ohne from/to), Tickets sind aber datumsgefiltert → bei Spielzeit-Filter ist die Auslastung zu klein.
→ Fix: `seatsCapacity($project, $from, $to)` über `eventsInRange`. Überschaubarer Eingriff, sollte vor der Demo rein.

### R3. Occupancy-Delta wird falsch gelesen (relative % statt Prozentpunkte)
`Dashboard.vue:83-85/297-302/313`: 80 %→90 % zeigt „▲ 12,5 % vs. Vorjahr" (relative Änderung) statt „+10 pp". Wird in einer Präsentation garantiert hinterfragt.
→ Quick Win: für Raten-KPIs Differenz in Prozentpunkten anzeigen.

### R4. Vorjahreslinie im Monatstrend zeigt flache 0-Linie bei fehlenden Vorjahresdaten
`Dashboard.vue:250/346-355`: `?? 0` → sieht aus wie „Vorjahr hatte 0 Besucher".
→ Quick Win: `null` liefern + `spanGaps:false`, dann wird die Linie einfach nicht gezeichnet.

### R5. Export vorher mit realistischer Datenmenge testen
`BiExportController.php:77`: `dispatchSync` — Export läuft synchron im Request (bewusst, kein Worker-Zwang). Bei vielen Projekten droht Timeout; der Poll-Mechanismus ist dadurch faktisch tot (Status ist sofort `ready`).
→ Vor der Demo einmal mit der echten Projektzahl durchspielen.

### R6. Excel-Export: zwei Dezimalkonventionen im selben Sheet
`BiExportService.php:246-249` + Blade-View: `revenue` roh mit Punkt (`1234.56`), `avg_price` deutsch (`1.234,56`), `occupancy` als String „85,0 %". Excel (DE) liest `1234.56` falsch; nichts ist summierbar.
→ Wenn der Export morgen gezeigt wird: mindestens `revenue` konsistent formatieren. Richtig: `FromArray` + `WithColumnFormatting` mit echten Zahlen (bekanntes Muster aus dem Personalplan-Export).

### R7. Crash-Risiko: Null-Deref bei Events ohne Start/Ende
`BiDerivedValuesService.php:84-85`: `$event->start_time->startOfDay()` ohne Guard, `end_time` ist nullable → Fatal Error killt die ganze Projekt-BI-Ansicht.
→ Quick Win (2 Zeilen): `if (!$event->start_time || !$event->end_time) continue;`

### R8. Dashboard-Performance: N+1 bei Buchungszählung + doppelter Vorjahres-Lauf
- `BiDerivedValuesService.php:123-142`: `$project->table()->first()` feuert trotz Eager-Load eine frische Query, plus Sage-Count je Projekt. `aggregate()` läuft 2× (aktuell + Vorjahr) → ~4 Queries/Projekt, bei 500 Projekten ≈ 2000 Queries pro Cache-Miss.
- Der Vorjahres-Lauf berechnet den kompletten Aufwand-Score neu, obwohl der zeitraum-unabhängig ist.
→ Quick-Win-Teil: `$project->table` (Property) statt `->table()->first()`. Größer: Booking-Counts memoizen, fürs Vorjahr nur die 6 KPIs rechnen.
→ Für morgen: 10-Min-Cache vorwärmen (Dashboard einmal vor der Demo aufrufen), dann ist der Punkt live entschärft.

---

## 2. Quick Wins (jeweils < ~1h, sichtbare Wirkung)

### Projekt-Tab
- **QW1. Speicher-Feedback:** Sämtliche Auto-Saves (Zellwerte, Totals, Kapazitäten, Kerndaten, Custom Fields) speichern still — keinerlei Bestätigung. `BiQuickEntryModal.vue` macht es mit `saveError` richtig; das Muster auf die Sektionen übertragen (kleines „Gespeichert ✓" pro Feld oder Toast).
- **QW2. Fehler sichtbar machen:** Alle `catch`-Blöcke machen nur `console.error` (`BiEventMetricsTable.vue:413`, `BiAudienceRevenueSection.vue:202ff`, u.v.m.) → fehlgeschlagener Save = stiller Datenverlust, der getippte Wert steht weiter im Feld. Mindestens ein `saveError`-Ref je Sektion.
- **QW3. Datenqualitäts-Banner klickbar machen** (`BusinessIntelligenceComponent.vue:42-46`): fehlende Punkte als Anker rendern → scrollt zur Karte und klappt sie auf. Plus grüner „Alles erfasst"-Zustand statt stillem Verschwinden — starkes Demo-Signal.
- **QW4. Moduswechsel-Warnung nur zeigen, wenn es Daten zu verlieren gibt** (`BiAudienceRevenueSection.vue:190`, `BiModeSwitchModal.vue`): beim Wechsel aus leerem Modus direkt umschalten; im Modal Kennzahl + Anzahl betroffener Einträge nennen. Zusätzlich: optimistischen Toggle bei Fehler zurückrollen (`:195-205`).
- **QW5. Custom-TextField feuert pro Tastendruck einen PATCH** (`BiCustomFieldsSection.vue:11-13`) — auf Blur-only umstellen wie die TextArea daneben.
- **QW6. Native `confirm()`-Dialoge** (Zeitaufwand löschen, Snapshot löschen, Tag löschen) sind hartkodiert Englisch und stilfremd → `ArtworkBaseModal`-Bestätigung + `$t()`.
- **QW7. Überbuchung >100 % rot markieren** (`BiEventMetricsTable.vue:145`) — aktuell wird gekappt und grün gefärbt; >100 % ist fast immer ein Tippfehler. Und bei fehlender Kapazität statt stummem „–" einen Tooltip „Kapazität hinterlegen" zeigen.
- **QW8. Snapshot-Sektion:** ein Satz Erklärung wozu Snapshots dienen + Datum auf heute vorbelegen (`BiSnapshotSection.vue:131`).
- **QW9. Effort-Buckets ohne Einheit** (`BiTimeEffortSection.vue:68-74`): „0-10" — Stunden? Einheit/Hilfetext ergänzen.

### Dashboard
- **QW10. Deutsche Zahlenformate durchziehen:** Prozentwerte nutzen `toFixed(1)` mit Punkt (`Dashboard.vue:84/185/313`), Chart-Achsen/Tooltips ohne Tausendertrennung (`:378`) — `Intl.NumberFormat('de-DE')` überall.
- **QW11. Aufwand-Score erklärbar machen:** Gewichte (`2·Verträge + 1·Buchungen + 1.5·offene Aufgaben + 0.5·Dokumente + 0.1·Stunden`) und Bucket-Map in den Dashboard-Payload geben (`BiDashboardService::getDashboardData:63-75`) + Tooltip „Wie berechnet sich der Score?". Gleiche Tooltips für Auslastung (= Tickets/Kapazität) und ≈-Schätzwerte an den KPI-Kacheln. **Billigster großer Präsentationsgewinn.**
- **QW12. „Spielzeit"-Preset initial als aktiv markieren** (`Dashboard.vue:239`) — Default ist die Spielzeit, aber kein Button ist hervorgehoben.
- **QW13. Leerzustände:** Monatstrend und Scatter verschwinden bei fehlenden Daten ersatzlos (`v-if`), Kategorie-Charts zeigen korrekt „No data" — leere Karte mit Hinweis („Scatter braucht ≥3 Produktionen mit Zahlen").
- **QW14. Ladezustand beim Zeitraumwechsel** (Opacity/Overlay während `loading`) — sonst wirkt der Reload wie „hängt".
- **QW15. 0 % vs. „nicht erfasst":** `BiDashboardService.php:120` reicht `?? 0` an `occupancyRate` → Projekte ohne Tickets zeigen 0 % statt „–". Null durchreichen.
- **QW16. Hinweis, dass der Kategorie-Filter nur die Tabelle filtert** + „Filter zurücksetzen"-Button + aktives Doughnut-Segment hervorheben.
- **QW17. `avg_price` wird berechnet, aber nirgends angezeigt** (`BiProjectMetricsService.php:153-160`) — als KPI/Spalte kostenlos zu haben.

### Export/Settings
- **QW18. Zeitraum in Dateiname + Titelzeile** (`BiExportService.php:83`): aktuell nur Timestamp — später nicht mehr nachvollziehbar, welcher Zeitraum exportiert wurde.
- **QW19. Export-Menüpunkt permission-gaten:** `ProjectSettingsHeader.vue:92-97` zeigt „BI Export" immer, Backend 403t → `canExportBiData` in `HandleInertiaRequests` sharen (analog `canViewBiDashboard`).
- **QW20. Preset löschen: Confirm-Dialog + Ownership-Prüfung** (`BiExportColumnPicker.vue:134`, `BiExportPresetController@destroy`) — aktuell löscht ein Klick jedes globale Preset.
- **QW21. Suchfeld im Spalten-Picker** (bei 30+ Spalten inkl. Tags/Custom-Fields).
- **QW22. Umsatz-Validierung ohne Obergrenze** (`UpdateBi*Request`): `max:9999999999.99` ergänzen, sonst DB-Exception statt Validierungsfehler.
- **QW23. Tag löschen warnt nicht bei Benutzung** (`BiEventTypeTagManager.vue:104`, Service prüft Zuordnungen nicht) → ConfirmModal + Warnung wenn Terminarten zugeordnet.

---

## 3. Workflows geradliniger machen (größer)

- **W1. Voll-Refetch nach jeder Zelle abschaffen** — wichtigster struktureller Punkt im Projekt-Tab. Jede Zelländerung lädt den kompletten `projects.bi.show`-Endpoint neu (`BusinessIntelligenceComponent.vue:254`); bei 30 Terminen = 30 Vollabfragen, plus **Race Condition**: der zurückkommende Refetch überschreibt laufende Eingaben in der nächsten Zelle. → Lokalen State aus der jeweiligen Save-Response aktualisieren.
- **W2. Erfassungs-Tabelle auf den Nach-Vorstellung-Flow optimieren** (`BiEventMetricsTable.vue`): Default-Sortierung neueste zuerst (aktuell älteste oben, `:208`), zukünftige Termine dimmen, Enter springt zur nächsten Zeile derselben Spalte (Serien-Erfassung „Besucher über 20 Termine"). Bulk-Ausfüllhilfe: Teilfehler bei `Promise.all` sichtbar machen (`:392-403`).
- **W3. Erinnerung nach Termin** (IDEEN A1): Scheduler-Notification „Termin war gestern, Zahlen fehlen" — koppelt die Erfassung an den natürlichen Zeitpunkt. Zusammen mit dem Datenlücken-Widget die runde Story: *System erinnert → Schnellerfassung → Lücke weg.*
- **W4. CSV-Import für Kassenzahlen** (IDEEN A4): deckt ~80 % einer Ticketing-Integration ab, ohne API-Abhängigkeit.
- **W5. Zeitaufwand editierbar machen** (`BiTimeEffortSection.vue`): Update-Route existiert (`web.php:3475`), UI bietet nur Anlegen/Löschen.
- **W6. Export asynchron** (`dispatchSync` → `dispatch` + Worker), dann den vorhandenen Poll echt nutzen; Poll-Cleanup bei Unmount + 4xx/5xx sofort abbrechen (`BiExport.js:12-37`).
- **W7. Dashboard-Tabelle:** Freitext-Suche, Pagination/Virtualisierung (aktuell alle Projekte im DOM), Export-Button der gefilterten Ansicht (Service existiert ja schon), Spalten-Presets „Leitungssicht" vs. „Steuerungssicht".
- **W8. Spalten-Reihenfolge im Export stabil halten** (`BiExportColumnPicker.vue:97-105`): ab-/anwählen schiebt Spalten ans Ende; Reihenfolge aus der Spaltendefinition ableiten, optional Drag-Sort. Dazu: Modal bezieht Spalten hartkodiert (`BiExportModal.vue:73-104`) parallel zum Backend-`columnLabelMap` — Drift-Gefahr, aus dem Backend speisen.

---

## 4. Inhaltlicher Ausbau (Roadmap-Story für die Vorstellung)

Als bewusste Roadmap präsentieren, nicht als Mangel:

- **I1. Kostenseite / Deckungsbeitrag — die größte inhaltliche Lücke.** Die Budget/Sage-Kette wird schon für die Buchungszählung durchlaufen; daraus sind Ausgabensumme je Projekt, Deckungsbeitrag (Umsatz − Kosten), Kostendeckungsgrad und €/Besucher ableitbar. Damit wird aus „Besucherstatistik" echtes BI.
- **I2. Personaleinsatz aus Schichten:** `shift_workers`-Stunden je Projekt → Personalstunden/Vorstellung, Personal-Intensität, grober Kosten-Proxy. Macht den Aufwand-Score durch eine harte Zahl ersetzbar/ergänzbar.
- **I3. Soll/Ist-Werte** (IDEEN B4): Zielbesucher/-umsatz je Projekt, Dashboard zeigt Abweichung — Steuerungswert, bevor alle Ist-Zahlen da sind.
- **I4. Auslastung pro Raum / Wochentag:** Rohdaten (`events.room`, `start_time`) sind im Dashboard-Service bereits geladen, aber ungenutzt. Raum-Ranking und Wochentags-Heatmap sind fast geschenkt.
- **I5. Top/Flop-Listen** (beste/schwächste Produktionen nach Umsatz/Auslastung) + Ø-Besucher je Vorstellung, Umsatz je Veranstaltungstag, No-Show-Quote (Tickets vs. Besucher).
- **I6. Saison-Vergleich** (zwei frei wählbare Zeiträume nebeneinander statt nur YoY) + Dashboard als PDF für Gremien (IDEEN C).
- **I7. Scatter-Achse umschaltbar machen** (Umsatz/Besucher-Toggle statt Auto-Heuristik): aktuell kippt EIN Projekt mit Umsatz die y-Achse global auf Umsatz → Besucher-only-Projekte kleben bei 0 und wirken ertraglos (`Dashboard.vue:435/441`).
- **Definitionsfragen klären (Konsistenz):**
  - „Auslastung" rechnet mit **verkauften Tickets**, Leitwert ist sonst **Besucher** — entweder umstellen oder sauber „Ticketauslastung" nennen.
  - `eventDays` zählt nur Starttage (3-Tage-Festival = 1 Tag), `countDistinctDaysForTag` zählt korrekt die volle Spanne — zwei Definitionen von „Veranstaltungstag" im selben Modul vereinheitlichen (`BiProjectMetricsService.php:91-102` vs. `BiDerivedValuesService.php:99`).

---

## 5. Substanz (nicht demo-sichtbar, aber wichtig)

- **S1. Autorisierung:** Die komplette Gruppe `projects/{project}/bi/*` läuft nur unter `auth` (`routes/web.php:3448`) — jeder Eingeloggte kann Umsatzdaten jedes Projekts lesen UND schreiben. Teil der bekannten modulübergreifenden Autorisierungs-Frage (IDEEN B3), aber Umsatz ist die sensibelste Datenklasse; hier zuerst nachziehen.
- **S2. BI-Field-Settings ohne jedes Gate:** `settings/bi/*` (`web.php:3499-3505`) nur `auth` — jeder Nutzer kann Custom-Fields anlegen/löschen/umsortieren. Menüpunkt ebenfalls `permission: true`.
- **S3. Null Tests im gesamten BI-Modul** (0 von 458 Testdateien, keine Factories). Höchste Priorität: `BiProjectMetricsService` (TOTAL vs. PER_EVENT, ≈-Fallback, n/a-Flags, null-statt-0) — reine Unit-Tests, ohne DB machbar.
- **S4. Presets:** `is_shared` wird hart auf `true` gesetzt, `created_by` nie ausgewertet — persönliche Presets sind totes Feature; kein Umbenennen, kein Default-Preset.
- **S5. Export-Inhalte hartkodiert deutsch** (`Ja/Nein`, Produktionsarten) statt `__()`.
- **S6. Monatschart schließt TOTAL-Projekte still aus** („by design", aber KPI-Kacheln oben zeigen sie → scheinbarer Widerspruch; Hinweis im Chart ergänzen).

---

## Empfehlung für heute (Restzeit vor der Vorstellung)

1. **Absichern:** R7 (Null-Guard, 2 Zeilen), R2 (Kapazität zeitraumfiltern), QW15 (0 % → „–").
2. **Sichtbar polieren:** R3 + R4 + QW10 (Zahlen-/Delta-Darstellung), QW12 (Spielzeit-Preset aktiv), QW11 (Score-Tooltip — macht die „Blackbox-Zahl" erklärbar).
3. **Demo vorbereiten:** Dashboard einmal aufrufen (Cache warm), Export mit echter Datenmenge testen (R5), Demo-Projekte im Per-Termin-Modus wählen (R1).
4. **Als Roadmap erzählen:** Abschnitt 4 (Kostenseite, Personal, Soll/Ist, Raum-Auslastung) + W3/W4 (Erinnerungen, CSV-Import) — bewusst als „nächste Ausbaustufe" framen.
