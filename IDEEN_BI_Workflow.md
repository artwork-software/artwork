# Ideen: BI-Modul — Workflow-Automatisierung & Ausbau

Stand: 2026-07-07. Gesammelte, bewusst **zurückgestellte** Ideen aus der BI-Überarbeitung.
Die Punkte unter „Automatismen" brauchen eine Kundenklärung (ob Automatik gewollt ist),
die Punkte unter „Größere Eingriffe" eine Produktentscheidung.

Bereits umgesetzt (zur Abgrenzung): KPI-Header + Karten im Projekt-Tab, vereinte
Per-Termin-Tabelle mit Multiselect-Ausfüllhilfe, „Nicht relevant"-Zustand pro Kennzahl,
Besucher-Schätzung aus Ticketzahlen (gekennzeichnet mit ≈), Schnellerfassung aus dem
Datenlücken-Widget im Dashboard, Monats-Zeitreihe, Aufwand-vs.-Ertrag-Quadrant,
Snapshot-Verlauf, vereinheitlichter Export.

---

## A. Automatismen (mit Kunde klären, ob gewollt)

### A1. Erinnerung nach Termin
Scheduler-Job: „Termin X war gestern, BI-Zahlen fehlen" als Notification an die
Projektleitung, sobald ein Termin vergangen ist und keine `bi_event_data`-Werte hat.
- Opt-in über GeneralSettings (global) und/oder pro Projekt.
- Frequenz drosseln (z. B. 1 Sammel-Notification pro Tag statt pro Termin).
- Infrastruktur vorhanden: Notification-System + Scheduler.
- **Warum:** Daten fehlen, weil niemand am Tag danach an die Maske denkt. Koppelt die
  Erfassung an den natürlichen Zeitpunkt.

### A2. Auto-Snapshots
Monatlicher Scheduler-Job legt pro aktivem Projekt einen Snapshot an
(Name z. B. „Auto 07/2026"). Der Snapshot-Verlaufs-Chart füllt sich dann von selbst.
- Aufräumen mitdenken (z. B. Auto-Snapshots älter als 24 Monate ausdünnen).
- Kennzeichnung auto vs. manuell (Spalte oder Namenskonvention).

### A3. Premierendatum aus BI-Tag ableiten
Tag „Premiere" auf Terminarten (Mechanik existiert: `bi_event_type_tags`) →
frühester so getaggter Termin wird als Premierendatum vorgeschlagen
(Vorschlag mit Übernehmen-Button, kein stilles Überschreiben).

### A4. CSV-Import für Kassenzahlen
Upload im Projekt-Tab (oder Settings): CSV aus dem Kassensystem, Spalten-Mapping
(Datum, ggf. Raum, Besucher/Tickets/Umsatz), Matching auf Termine per Datum+Raum,
Vorschau mit Konfliktanzeige, dann Bulk-Upsert in `bi_event_data`.
- Deckt ~80 % des Nutzens einer Ticketing-Integration ohne API-Abhängigkeit ab.

## B. Größere Eingriffe (Produktentscheidung nötig)

### B1. Umsatz aus Sage/Budget ableiten
Ticketumsatz aus `SageAssignedData`/Budget-Positionen statt Doppelpflege.
Braucht Konvention, welche Positionen „Ticketumsatz" sind (Markierung an
MainPosition/SubPosition o. ä.). Als optionale Quelle je Projekt umschaltbar.

### B2. Ticketing-Integration
Direkte Anbindung (Reservix, Eventim, Pretix, ticket.io …) — Erfassung entfällt
komplett. Groß: Auth, Mapping Termin↔Veranstaltung, Sync-Strategie.

### B3. Eigene BI-Erfassungs-Berechtigung
Aktuell hängt die Erfassung an `canEditComponent` (Projekt-Schreibrecht, nur
frontend-gated). Eigenes Recht „BI-Zahlen erfassen" (z. B. für Abendspielleitung/Kasse)
+ serverseitige Prüfung auf den `projects/{project}/bi/*`-Routes.
Hängt mit der bekannten modulübergreifenden Autorisierungs-Frage zusammen
(siehe AUDIT_Live_Testlauf_2026-06-25.md).

### B4. Plan-Werte (Soll/Ist)
Zielbesucher/-umsatz pro Projekt (`bi_project_data`-Erweiterung), Dashboard zeigt
Soll/Ist-Abweichung. Gibt Steuerungswert, bevor alle Ist-Zahlen erfasst sind.

## C. Kleinere offene Ideen

- Saison-Vergleich im Dashboard (zwei Zeiträume nebeneinander statt nur YoY-Delta).
- Dashboard als Druck-/PDF-Ansicht für Gremien.
- Datenlücken-Hinweis auch im Projekt-Tab-Header der Projektübersicht (Badge).
- Zeitaufwands-Änderungen bumpen die Dashboard-Cache-Version bisher nicht
  (nur `BiProjectDataService`-Writes tun das); bei Bedarf in
  `BiTimeEffortController` nachziehen — Auswirkung nur auf den Effort-Score,
  Cache-TTL ist ohnehin 10 Minuten.
