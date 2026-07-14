# Konzept: Design-Overhaul für die Schichtplan-Wochenansicht (ShiftPlan.vue)

Stand: 2026-07-14 · Prüfung der Übertragbarkeit des Kalender-Zoom-/Anzeige-Redesigns auf `ShiftPlan.vue`.
**Nicht Thema:** Tagesansicht (ShiftPlanDailyView) und Listenansicht — bleiben unangetastet.

## Kern-Erkenntnis: Der Schichtplan ist NICHT der Kalender

Die 1:1-Übertragung des Kalender-Konzepts funktioniert nicht, weil drei Dinge grundlegend anders sind:

1. **Transponiertes Grid:** Im Schichtplan sind **Räume = Zeilen** und **Tage = Spalten** (Kalender: umgekehrt). „Mehr auf einen Blick" heißt hier also primär **mehr Tage horizontal** → der Zoom muss die **Spaltenbreite** steuern, nicht die Zeilenhöhe. Das ist exakt invers zum Kalender (dort: Breite fix, Höhe zoomt).
2. **Zwei gekoppelte Grids:** Haupt-Grid (Räume×Tage) + unterer Userbereich (Worker×Tage) sind zwei getrennte, voll virtualisierte `Virtual2DGrid`s, horizontal scroll-synchronisiert. Die Spaltenbreiten (202px Tag / 130px KW / 191,5px Sticky-Spalte) sind **an beiden Stellen hart kodiert** und müssen exakt synchron bleiben, sonst driften die Raster.
3. **Canvas-Zeilenhöhen-Mechanik:** Bei `expand_days` wird die Raumzeilenhöhe per Canvas-Textmessung aus hartkodierten px-Konstanten geschätzt (`summarizeCell`, `cellFont`, `measureBaselineMetrics`). Jede Breiten-/Schriftänderung invalidiert diese Messung — es gibt aber bereits einen Settings-Change-Pfad, der Caches leert und neu misst (ShiftPlan.vue ~Z. 1900-1921). Ein Zoom-Wechsel kann genau diesen Pfad nutzen.

**Gute Nachricht zur Ladezeit-Frage:** Der Schichtplan lädt den gesamten Zeitraum (Kappe: 6 Monate) in einem Batch (`shift.plan.meta` + `shift.plan.rooms.batch`). **Raus-Zoomen lädt also keine zusätzlichen Daten** — es erhöht nur die Zahl gleichzeitig gerenderter Zellen, und die deckelt die vorhandene Zeilen+Spalten-Virtualisierung. Der Mehraufwand ist reine Renderlast (linear mit sichtbaren Spalten, im Userbereich × sichtbare Worker-Zeilen), kein Netzwerk-/Backend-Aufwand.

## Umsetzbarkeits- & Sinnhaftigkeits-Bewertung

| Anpassung | Umsetzbar? | Sinnvoll? | Risiko |
|---|---|---|---|
| Raumfarben (Chip an der Raumzeile) | Ja, einfach | Ja — gleiche Orientierungshilfe wie im Kalender | gering |
| Wochenend-/Feiertags-Tint (als **Spalten**-Färbung) | Ja | Ja — heute ist Wochenende grau (`bg-backgroundGray`), Feiertage nur Badge im Header; unterer Userbereich färbt gar nicht | gering (Tint-Map vorberechnen!) |
| Kräftigere Tages-/Raum-Abgrenzung | Ja, trivial | Ja — heute dashed/uneinheitlich | gering |
| Zoom (= Spaltenbreite + Kompakt-Schichtkarte) | Ja, mit Aufwand | Ja, mit angepasster Semantik (siehe unten) | mittel (expand_days-Messung, Grid-Sync, Perf) |
| Zoom > 100 % (Vergrößern) | möglich | **Nein** — Barrierefreiheit deckt der Browser-Zoom/Kalender ab; Schichtplan-Bedarf ist Dichte | — |

---

## Phase A — Farben & Abgrenzung (~6–9h, geringes Risiko)

1. **Raumfarben:** Farb-Chip am Raumnamen in der linken Sticky-Spalte (gleiche Optik wie `SingleRoomInHeader` im Kalender: Hintergrund = `getEffectiveColor`, Auto-Kontrast-Text). Zusätzlich optional ein 3px-Farbstreifen an der linken Kante der Raumzeile über die volle Höhe (Orientierung beim horizontalen Scrollen).
   *Backend:* Prüfen/ergänzen, dass `shift.plan.meta` bzw. `rooms.batch` die effektive Raumfarbe liefert (`ShiftPlanService`, analog `MapRoomsToContentForCalendar` → `roomColor`).
2. **Wochenende/Feiertage als Spalten-Tint** (Tag = Spalte!):
   - Wochenende: helles Blau `#eff6ff` statt des heutigen Grau (high_contrast: `#dbeafe`).
   - Feiertage: aus `holiday.color` abgeleitet — eintägige 20 % Alpha, mehrtägige (Ferien) 10 %, eintägige gewinnen. Identische Regeln wie im Kalender → konsistentes Farbvokabular.
   - Gilt für Haupt-Grid-Zellen, **Tagesheader** (dort als farbiger Unterstrich/Balken, da der Header dunkel ist) **und den unteren Userbereich** (heute komplett ungefärbt — die Orientierung „welche Spalte ist Samstag" fehlt dort völlig).
   - **Performance-Pflicht:** Tint pro Tag EINMAL in einer `Map<dayKey, color>` vorberechnen (computed), Zellen greifen nur per Lookup zu. Kein `holidays.find()` im Zell-Render-Pfad.
3. **Abgrenzung:** Raumzeilen-Linie von dashed auf durchgezogen (gray-300, high_contrast gray-500), vertikale Tageslinie wie im Kalender (18 % Schwarz inline), KW-Trennspalte behält ihre dickere Kante. Gleiche Werte wie im Kalender → ein System.
4. **QoL: „Heute"-Spalte hervorheben** — dezenter Rahmen/Tint + markierter Tagesheader. Fehlt heute komplett und kostet fast nichts.
5. **QoL: Monatsgrenze vertikal** — beim 1. des Monats eine kräftigere Trennlinie + „Monat JJJJ" im Tagesheader (das Pendant zum Monatsbalken des Kalenders, nur um 90° gedreht).

## Phase B — Zoom als Dichteregler für Spaltenbreite (~10–14h, mittleres Risiko)

**Semantik (bewusst anders als im Kalender):** Zoom steuert die **Tagesspaltenbreite**; die Raumzeilenhöhe bleibt inhaltsgetrieben (112px bzw. expand_days-Messung). Vorschlag: Stufen **100 % (202px) · 75 % (~152px) · 55 % (~112px)**, Dropdown in der `ShiftPlanFunctionBar` (HeadlessUI-Menu-Muster existiert dort bereits). Bei 55 % sind ~2× so viele Tage sichtbar — auf einem 1700px-Screen gut 2 Wochen statt 1.

1. **Gemeinsame reaktive Breiten-Quelle** `useShiftPlanZoom` (analog `useCalendarZoom`): ersetzt die vier hartkodierten Stellen (`shiftColWidth`, `kwColWidth`, `shiftLeftWidth` im Haupt-Grid + `202`/`191.5` im unteren `Virtual2DGrid`). Sticky-Spalte (Raumnamen) bleibt **fix** — nur Tagesspalten skalieren. Persistenz: neues Feld `zoom_factor` (o.ä.) in `user_shift_plan_settings`, debounced, ohne Reload. **Eigener Faktor, nicht `users.zoom_factor`** — sonst verstellt der Kalender-Zoom den Schichtplan mit.
2. **Kompakte Schichtkarte** unter der Schwelle (< 100 %): einzeilig „Zeit · Gewerk-Kürzel · Besetzungsampel (2/3)", Klick öffnet wie gewohnt; Details per Hover-Tooltip (Muster `CompactEventInCalendar`). Eigene leichte Komponente, kein `v-if`-Umbau von `SingleShiftInRoom` — im Schichtplan gilt „nichts Teures in den Render-Pfad".
3. **expand_days-Integration:** Zoom-Wechsel läuft über den bestehenden Settings-Change-Pfad — Text-/Summary-Caches leeren, `measureBaselineMetrics` + `recomputeRowHeights` neu anstoßen. Die Breiten-Konstanten der Messung (`cellInnerWidth`, `pgBarTextWidth`, `eventBarTextWidth`) werden von der Zoom-Quelle abgeleitet statt fix. Bei Kompaktkarten ist die Messung einfacher (feste einzeilige Höhe) → `summarizeCell` bekommt einen Kompakt-Zweig, der OHNE Canvas auskommt (feste Höhe pro Schicht) — das macht Zoom-out sogar *billiger* statt teurer.
4. **Unterer Userbereich:** erbt die Spaltenbreite automatisch aus der gemeinsamen Quelle (Raster bleiben synchron). Worker-Zeilenhöhe bleibt am bestehenden Compact-Toggle (32/48px) — sie hängt an Avataren/Namen, nicht an der Tagesbreite. `ShiftPlanCell`-Inhalte (Schicht-Chips) müssen bei schmalen Spalten stärker trunkieren — prüfen, ob der bestehende Chip-Inhalt bei ~112px noch lesbar ist, sonst Kurzform (nur Zeit).
5. **Ctrl/Cmd+Scroll** über dem Plan: gleiche Geste wie im Kalender, gleiche Drossel.
6. **Perf-Gate (Pflicht vor Merge):** Messen bei 55 % mit realen Datenmengen — sichtbare Zellen Haupt-Grid + Userbereich, Scroll-FPS, `recomputeRowHeights`-Dauer bei expand_days. Abbruchkriterium definieren (z.B. Scroll-Jank > 16ms-Frames gehäuft → Overscan reduzieren oder 55 %-Stufe streichen).

### Bewusst NICHT übernommen aus dem Kalender

- **Zeilenhöhen-Zoom:** Raumzeilen sind inhaltsgetrieben (Schichtkarten stapeln sich); eine 33px-Raumzeile wäre nutzlos.
- **Zoom > 100 %:** kein Bedarf im Dichte-Use-Case.
- **Monatsansicht-Preset:** bei 6-Monats-Batch + 55 % sieht man bereits Wochen; ein „Monat"-Preset = Dropdown-Eintrag, der Zeitraum + Zoomstufe setzt — als optionales Add-on notiert, nicht Kern.

## Entscheidungen (2026-07-14, bestätigt)

1. **Zoom = Spaltenbreite** ✓
2. **3 Stufen** (55/75/100 %) ✓
3. **Kompaktkarte:** Zeit, Gewerk, Besetzt-Zähler **gesamt** (alle Funktionen summiert) ✓
4. **Worker-Bereich:** Compact-Toggle bleibt unabhängig vom Zoom ✓

## Umsetzungsstatus (2026-07-14) — PHASE A + B UMGESETZT

- **Phase A:** `roomColor` im `shift.plan.meta`-Payload (Batch lieferte es bereits); Raumfarben-Chip in der Sticky-Spalte (Auto-Kontrast, sticky bei hohen Zeilen); Wochenend-/Feiertags-/Heute-Tint als vorberechnete `dayTintByKey`-Map (light für Haupt-Grid, dark für den dunklen Userbereich, accent als Header-Unterstrich); Feiertag > Heute > Wochenende, Ferien dezenter (Kalender-Regeln); vertikale Tageslinien (18 % Schwarz, Monatsgrenze 2px/40 %), Raumzeilen durchgezogen statt dashed; Monats-Kurzbadge („Aug.") im Tagesheader am Monatsersten; Heute-Spalte: indigo Header-Text + Tint.
- **Phase B:** `useShiftPlanZoom.js` (Stufen 0.55/0.75/1, debounced Persist auf neues Feld `user_shift_plan_settings.zoom_factor`, Migration `2026_07_14_120000`, Route `user.update.shift_plan_zoom_factor` mit Validierung); `shiftColWidth` + Textmess-Breiten (`cellInnerWidth` etc.) reaktiv aus dem Zoom; unteres Worker-Grid nutzt dieselben Breiten-Quellen; Zoom-Wechsel hängt am bestehenden Cache-Clear/Remeasure-Watcher; `CompactShiftInRoom.vue` (Gewerk-Kürzel + Startzeit + (x/y) + Ampel, title-Tooltip mit voller Zeit, Klick öffnet Schicht-Modal; volle Karte bleibt bei Multi-Edit/Highlight); Zoom-Dropdown + Kompakt-Chip in der ShiftPlanFunctionBar; Ctrl/Cmd+Scroll; `isCompactShiftZoom` in beiden `v-memo`-Arrays (sonst verschluckt das Memo den Umschalt-Wechsel); Tagesheader zeigt bei Kompakt das Datum ohne Jahr.
- **Perf-Gate bestanden:** 55 % + expand_days: 0 abgeschnittene Zellen (Canvas-Messung rechnet mit gezoomten Breiten), 128 gerenderte Zellen / ~13k DOM-Knoten / Relayout ~10 ms / Heap 77 MB. Zoom-Wechsel inkl. Ctrl+Scroll live ohne Reload, Persistenz in DB verifiziert (0.55).
- **Tests:** 2 neue Tests in `UserCalendarZoomDisplaySettingsTest` (Persist inkl. Settings-Autoanlage, Range-Validierung).

## Aufwand gesamt

| Phase | Inhalt | Schätzung |
|---|---|---|
| A | Raumfarben, Spalten-Tint (inkl. Userbereich + Header), Linien, Heute-Highlight, Monatsgrenze | 6–9h |
| B | Zoom-Quelle, Dropdown, Kompaktkarte, expand_days-Integration, Ctrl+Scroll, Perf-Messung | 10–14h |
| — | Regressionstest (expand_days an/aus, Multi-Edit, Broadcast-Updates/`room.__v`, Userbereich-Resize) | 3–4h |
| **Σ** | | **19–27h** |

Empfehlung: **Phase A vorziehen** (hoher Nutzen, geringes Risiko, unabhängig mergebar), Phase B danach als eigener Branch mit Perf-Gate.
