# Konzept: Kalender-Zoom & Anzeige-Redesign

Stand: 2026-07-13 · Basis: Kundenanforderungen (Kampnagel/Hau) + Code-Analyse `BaseCalendar.vue` & Umfeld

## Getroffene Grundsatzentscheidungen

| Entscheidung | Ergebnis |
|---|---|
| Zoom-Modell | **Ein Regler = Dichte.** Zoom steuert primär die Zeilenhöhe (Informationsdichte). Spaltenbreite bleibt konstant. Schrift in Raumheader + Datumsspalte fix. |
| Spaltenbreite | **Eine globale Breite** für alle Raumspalten, einstellbar in den Anzeigeeinstellungen, pro User gespeichert. Drag-Resize pro Raum = optionale Ausbaustufe. |
| Monatsansicht | **Zoom-Preset „Monatsansicht"** im Stufen-Dropdown (kompakteste Stufe + Sprung zum Monatsanfang). Echtes Mo–So-Monatsraster = separates Folgepaket, hier nicht geplant. |
| Befüllungsmultiedit | **Separates Konzept** (siehe „Folgepakete"). |

---

## Ist-Zustand (Code-Analyse)

- Ein `zoom_factor` (auf `users`, 0.4–1.4 in 0.2-Schritten) skaliert **alles gleichzeitig**:
  - Raumspaltenbreite: `zoom × 212px` (`BaseCalendar.vue:465`)
  - Zeilenhöhe: `zoom × 212px` (`BaseCalendar.vue:467`)
  - Kachelbreite: `zoom × 196px`, Schrift: `max(zoom × 0.875rem, 10px)`
- **Bei 40 % ist die Zeile heute ~85px hoch** → Ziel ≤33px erfordert eine eigene, steilere Höhenkurve.
- Jede Zoom-Änderung macht einen **kompletten Seiten-Reload** (`preserveState: false` in `FunctionBarCalendar.vue:576-583`), weil 7 Komponenten den Wert unabhängig aus den Page-Props lesen (BaseCalendar, CalendarHeader, SingleDayInCalendar, SingleRoomInHeader, FullEventInCalendar, …).
- Datumsspalte: Wochentag + Datum werden unter Zoom 0.8 **komplett ausgeblendet** (`SingleDayInCalendar.vue:5-8`) — genau der beklagte Painpoint.
- Kompaktkachel heute: unter 0.6 nur Info-Icon + ggf. Name, Bearbeiten nur über Umwege; `FullEventInCalendar.vue` ist mit 1277 Zeilen zu schwer für massenhaftes Rendern bei Monatsdichte.
- Wochenende: ganze Zeile `bg-gray-50` (grau — soll ersetzt werden). Feiertage: nur Tooltip-Badge in der Datumsspalte, `CalendarHolidayDTO` liefert bereits eine `color`.
- Monats-Sentinels für Scroll-Erkennung existieren bereits (`registerMonthSentinel` in BaseCalendar) — Anknüpfungspunkt für den Monatsbalken.
- Raumfarben (uncommitteter Stand 2026-07-13) erscheinen nur als Chip im Raumheader → geringe Kollisionsgefahr mit Wochenend-Einfärbung.

---

## Phase 0 — Technisches Fundament: Zoom reaktiv & entkoppelt (~5–7h)

Ohne diese Phase ist weder Ctrl+Scroll noch ein Schnellwahl-Dropdown sinnvoll umsetzbar.

1. **Zentraler Zoom-Store** (Composable `useCalendarZoom`, module-scoped): ersetzt die 7 unabhängigen `usePage().props.auth.user.zoom_factor`-Reads. Eine Quelle, reaktiv.
2. **Persistenz ohne Reload:** debounced PATCH auf `user.update.zoom_factor` (bestehende Route), `preserveState: true`. Zoom-Wechsel fühlt sich sofort an statt Full-Reload.
3. **Layout über CSS Custom Properties** am Grid-Root (`--cal-row-h`, `--cal-col-w`, `--cal-font`): eine Style-Änderung statt Re-Render tausender Zellen-Inline-Styles. Wichtig für flüssiges Ctrl+Scroll.
4. **Neue Skalen (Entkopplung):**
   - `--cal-col-w` = globale Breiteneinstellung (Phase 1), **unabhängig vom Zoom**.
   - `--cal-row-h` = eigene Kurve, Richtwerte (Feintuning beim Umsetzen):

     | Zoomstufe | Basiszeilenhöhe (heute) | Basiszeilenhöhe (neu) |
     |---|---|---|
     | 40 % („Monatsansicht") | ~85px | **33px** |
     | 60 % | ~127px | ~60px |
     | 80 % | ~170px | ~110px |
     | 100 % | 212px | 212px (unverändert) |
     | 120/140 % | 254/297px | wie heute |
   - Basiszeilenhöhe = `minHeight`; bei mehreren Terminen in einer Zelle wächst die Zeile weiter mit (wie heute). *(→ offene Frage 1)*
   - Über 100 %: Verhalten wie heute (Schrift/Höhe wachsen), Breite bleibt konstant — wer dauerhaft „größer" will, stellt die Spaltenbreite auf Breit.
5. **Migration:** bestehende `zoom_factor`-Werte bleiben gültig (gleiche Stufen), keine Datenmigration nötig.

**Performance-Leitplanke:** Kleinere Zeilen ⇒ deutlich mehr gleichzeitig sichtbare Zellen (Monatsdichte: ~30 Tage × alle Räume). Die Zell-Virtualisierung (IntersectionObserver) bleibt, aber die Kompaktkachel (Phase 3) muss eine bewusst leichte Komponente sein. Vor/Nach-Messung Pflicht.

---

## Phase 1 — Raumspalten & Header (~3–4h)

1. **Globale Spaltenbreite** in den Anzeigeeinstellungen: Presets *Schmal (160px) / Standard (212px) / Breit (280px)* oder Slider 160–320px. Neue Spalte `calendar_column_width` in `user_calendar_settings` (Migration).
2. **Dünne Spaltentrennlinien:** 1px-Linie zwischen Raumspalten; auf getöntem Hintergrund (Wochenende/Feiertag) weiß, sonst sehr helles Grau. Berücksichtigt `high_contrast`-Setting (dann kräftiger).
3. **Raumheader kompakter:** Höhe reduzieren, Raumnamen mit `truncate` + „…" abkürzen, voller Name als Tooltip. Raumfarben-Chip bleibt.
4. **Schrift im Raumheader fix** — wird vom Zoom komplett ausgenommen.

---

## Phase 2 — Datumsspalte & Tages-Einfärbung (~5–7h)

1. **Inhalte bleiben bei jedem Zoom erhalten:** Wochentagskürzel + Datum verschwinden nicht mehr (heute unter 80 % weg). Schrift fix; nur bei 40 % eine Stufe kleiner, damit 33px-Zeilen aufgehen.
2. **Typo-Hierarchie:** Datum größer (`DD.MM.`), Jahreszahl entfällt in der Tageszeile — sie steht künftig im Monatsbalken. KW-Anzeige (montags) bleibt.
3. **Monatsbalken:** Bei jedem Monatswechsel eine schmale Trennzeile über die volle Grid-Breite mit „**Monat JJJJ**". Ausrichtung mittig zur sichtbaren Bildschirmbreite (sticky-left-Container in Viewport-Breite); falls das mit dem horizontalen Scroll-Container hakt, Fallback linksbündig (sticky). Dezent: getönter Hintergrund + feine Doppellinie.
4. **Wochenenden & Feiertage farblich:**
   - Wochenende: sehr helles Blau statt Grau (z. B. `#F3F6FC`-Klasse) — bewusst dezent, damit es nicht mit Termintyp- und Raumfarben kollidiert.
   - Feiertage: eigener heller Ton (z. B. warmes Hellgelb) oder abgeleitet aus `holiday.color` (existiert im DTO) *(→ offene Frage 2)*. Feiertag schlägt Wochenende.
   - Einfärbung gilt für die **ganze Zeile inklusive Datumsspalte** (Anforderung).
   - `high_contrast`: kräftigere Töne + zusätzlich Textkennzeichnung (z. B. Feiertagsname im Tooltip bleibt), nicht nur Farbe.

---

## Phase 3 — Kompakte Terminkachel (~7–9h)

1. **Neue leichte Komponente `CompactEventInCalendar.vue`** (statt weiterer `v-if`-Zweige im 1277-Zeilen-`FullEventInCalendar`). Aktiv unterhalb einer Zoom-Schwelle (~<70 %). Aufbau einzeilig:

   ```
   [ 19:30  Titel/Projekt……………… ]   ← Hintergrund = Termintyp-Farbe
   [ GT     Aufbau Halle K2………… ]   ← ganztägig: „GT" statt Zeit
   ```
   - Nur **Startzeit** im Format `00:00`; ganztägig → Kürzel **„GT"**.
   - Kein Info-Icon, kein Uhr-Icon, kein 3-Punkte-Umweg.
2. **Dynamischer Titel** (Fallback-Kette):
   1. Termintitel (wenn zusätzlich Projekt: „Titel · Projekt", soweit Platz)
   2. sonst Projekttitel
   3. sonst Name des Termintyps
3. **Anzeigeeinstellung „Künstler:innen statt Termintitel"** (Hau): neue Spalte in `user_calendar_settings`, ersetzt in der Fallback-Kette den Termintitel durch die Künstler:innen-Namen des Projekts, Fallbacks bleiben.
4. **Interaktion bei kompaktem Zoom:**
   - **Klick auf Kachel → Termin-Modal** (Infos + Bearbeiten, vorhandenes Edit-Modal), von dort Sprung ins zugewiesene Projekt. Bearbeiten bleibt so auf jeder Zoomstufe erreichbar.
   - **QoL: Hover-Tooltip** mit vollen Infos (Titel, Zeit von–bis, Projekt, Status) — ersetzt das bisherige Info-Icon eleganter.
5. Ziel-Layout gemäß Beispielbild („Beispiel - Terminkachel.jpg"): flache Kachel, Zeit vorn, Text truncated.

---

## Phase 4 — Zoom-Bedienung (~4–5h)

1. **Schnellwahl-Dropdown:** Klick auf die %-Anzeige öffnet ein Dropdown mit allen Stufen. Einträge mit Orientierungslabel:
   - `Monatsansicht (40 %)` ← Preset: kompakteste Stufe **+ Scroll zum Monatsanfang**
   - `60 %` · `80 %` · `100 % – Standard` · `120 %` · `140 %`
   - Die +/−-Icons entfallen *(→ offene Frage 3)*.
2. **Ctrl+Scroll / Cmd+Scroll** über dem Kalenderbereich: zoomt durch die Stufen (mit `preventDefault`, damit der Browser-Zoom nicht anspringt), debounced persistiert. Dank Phase 0 ohne Reload flüssig.
3. Mobile-Variante der Funktionsleiste (`FunctionBarCalendar.vue:280-300`) zieht nach.

---

## Aufwand gesamt

| Phase | Inhalt | Schätzung |
|---|---|---|
| 0 | Zoom reaktiv, Entkopplung, CSS-Variablen | 5–7h |
| 1 | Spaltenbreite-Setting, Trennlinien, Header | 3–4h |
| 2 | Datumsspalte, Monatsbalken, WE/Feiertage | 5–7h |
| 3 | Kompaktkachel + Künstler:innen-Setting | 7–9h |
| 4 | Dropdown, Monats-Preset, Ctrl+Scroll | 4–5h |
| — | Perf-Messung + Regressionstest über alle Zoomstufen/Ansichten (Tagesansicht, At-a-glance, expand_days) | 3–4h |
| **Σ** | | **27–36h** |

Sinnvolle Ausbaureihenfolge: 0 → 1 → 3 → 2 → 4 (nach Phase 3 ist der Kern-Painpoint „kompakt + lesbar" gelöst; 2 und 4 sind unabhängig voneinander).

---

## Beantwortete Fragen (Entscheidungen vom 2026-07-14)

1. **33px-Zeile bei vollen Tagen:** Bei aktivem „Tage expandieren" wächst die Zeile mit dem Inhalt; ohne das Setting bleibt sie bei 33px und die Zelle scrollt intern (wie bisher).
2. **Feiertagsfarbe:** aus `holiday.color` abgeleitet mit reduzierter Deckkraft (20 %, high_contrast 35 %) — erkennbar, aber dezent. **Zusatzentscheidung bei der Umsetzung:** Nur *eintägige* Feiertage färben die Zeile; mehrtägige Zeiträume (Ferien) würden ganze Wochen einfärben und bleiben deshalb nur als Tooltip in der Datumsspalte sichtbar.
3. **+/−-Buttons:** entfernt — Dropdown + Ctrl/Cmd+Scroll ersetzen sie.
4. **Kompakt-Schwelle:** unter 80 % (bei 80 % noch Vollansicht). In der Funktionsleiste erscheint im Kompaktmodus ein „Kompakt"-Hinweis-Chip mit Tooltip, dass Klick auf den Termin Details/Bearbeitung öffnet (das Kachel-Menü entfällt dort).
5. **Tagesansicht & „Auf einen Blick":** Layout unangetastet; sie übernehmen nur den reaktiven Zoom-Store bzw. die feste Spaltenbreite (At-a-glance).

## Umsetzungsstatus (2026-07-14) — ALLE PHASEN UMGESETZT

- **Phase 0:** `useCalendarZoom.js` (zentraler reaktiver Store, debounced axios-Persist ohne Full-Reload, Zeilenhöhen-Kurve 40 %→33px … 100 %→212px, Legacy-Werte werden auf Stufen gesnappt). Alle 9 Konsumenten umgestellt.
- **Phase 1:** `calendar_column_width` in `user_calendar_settings` (Migration `2026_07_14_090000`), Listbox-Presets im Kalender-Settings-Modal (160/212/280/320px), vertikale Trennlinien (high_contrast-aware), Raumheader h-16→h-11 mit truncate+Tooltip und fixer Schrift.
- **Phase 2:** Datumsspalte fix 90px, Wochentag+Datum bei jedem Zoom (kompakt einzeilig „Mi 01.07."), Jahr entfällt (steht im Monatsbalken), KW bleibt; Monatsbalken je Monatsgrenze mit „Monat JJJJ" (sticky mittig zur Bildschirmbreite); Wochenende hellblau `#eff6ff`, Feiertage aus holiday.color, ganze Zeile inkl. Datumsspalte.
- **Phase 3:** `CompactEventInCalendar.vue` (leichte Einzeiler-Kachel: Zeit/„GT" + Titel·Projekt, Termintyp-Farblogik identisch zu Full, Hover-Tooltip mit Volldaten, Klick öffnet Termin-Modal); Setting `show_artist_names_as_title`; Kompakt-Chip in der Funktionsleiste.
- **Phase 4:** %-Anzeige ist Dropdown (Monatsansicht (40 %) + Stufen, aktueller Wert mit Häkchen), +/− entfernt (Desktop & Mobile-Menü), Ctrl/Cmd+Scroll zoomt stufig (preventDefault, gedrosselt), Monatsansicht scrollt zum Anfang des fokussierten Monats.
- **Tests:** `UserCalendarZoomDisplaySettingsTest` (4 Tests: Persistenz beider Settings, Defaults, Schichtplan-Save unberührt, zoom_factor-PATCH). Browser-verifiziert auf artwork.ddev.site (Dropdown, Monatsansicht, Kompaktkachel-Klick→Modal, Ctrl+Scroll, Spaltenbreite 280px in Zellen+Header synchron, Wochenend-/Feiertags-Tint, Monatsbalken).

### Nachbesserungen (Review-Feedback 2026-07-14)

- Raumspaltenbreite-Dropdown steht im Settings-Modal jetzt **am Ende der Rubrik** „Erscheinungsbild & Barrierefreiheit" (eigener Block, zersprengt das Checkbox-Raster nicht mehr).
- Zoom-Dropdown: Trigger im normalen `ui-button`-Stil (weiß, nicht ausgegraut), Panel + Einträge in **BaseMenu/BaseMenuItem-Optik** (IconPercentage je Stufe, IconCheck auf der aktiven).
- Datumsspalte zeigt ab 80 % Zoom das **Jahr klein** neben dem Datum („01.07. 2026").
- Kompakt-Chip nutzt `ToolTipWithTextComponent` (einheitlicher Hover-Tooltip): erklärt Schwelle (<80 %), Klick-Verhalten und dass das Kachel-Menü entfällt.
- **Berechtigungen verifiziert:** User ohne Termin-Bearbeitungsrecht (kein Creator/Room-Admin/Admin/`create events without request`) bekommt beim Kompaktkachel-Klick automatisch den bestehenden **Read-only-Zweig** des EventComponent („Event overview": Typ, Status, Zeit, Raum, Projekt, nur Schließen) — Gating über vorhandenes `canEdit`. Dabei gefixt: Read-only-Datum war ISO, jetzt DD.MM.YYYY. Testuser: kompakt.test@artwork.software (Dev-DB, ID 712, ohne Rollen/Permissions).

### Nachbesserungen Runde 2 (Review-Feedback 2026-07-14, nachmittags)

- **Trennlinien kräftiger:** Raumspalten-Linie von 8 % auf 18 % Schwarz (high_contrast 40 %); Tagesgrenze jetzt durchgezogene Linie auf der ganzen Zeile (gray-300, high_contrast gray-500) statt gestrichelter Zell-Border.
- **Monatsbalken schwarz:** `bg-artwork-navigation-background` mit weißer Schrift (wie die dunklen Leisten anderer Screens), Label weiterhin sticky mittig.
- **„Termine ohne Raum":** Der vollflächige rote Banner ist ersetzt durch einen **dezenten amber Chip in der Funktionsleiste** („n ohne Raum" mit Warndreieck + Tooltip); Klick öffnet wie bisher das Zuweisungs-Modal. Sichtbar, aber nicht mehr schreiend.
- **Tagesansicht bis 1 Monat:** Serverseitige Kappe in `EventController` von 7 auf **31 Tage** erhöht (Standard- und Planungskalender; Schichtplan bleibt bewusst bei 7). Warntext angepasst. Gemessen: 31 Tage Tagesansicht ≈ 50k DOM-Knoten, Heap ~111 MB, Full-Relayout 82 ms, Server-Antworten < 400 ms — einmaliger Aufbau dauert ein paar Sekunden, danach flüssig.
- **Feiertagssymbol im Kompaktmodus:** HolidayToolTip erscheint jetzt auch bei < 80 % inline neben dem Datum.
- **Ferien-Tint:** Mehrtägige Feiertage (Ferien) färben die Zeile jetzt doch — aber mit 10 % Alpha (high_contrast 20 %) deutlich dezenter als eintägige Feiertage (20 %/35 %); eintägige gewinnen bei Überlappung. Damit sind Ferien erkennbar, ohne Wochen zu überladen.
- **Projektsuche:** Suchfeld auf w-72 verbreitert (Label lief aus dem Feld), Präfix „Projektzeitraum:" → „Projekt:".

## Folgepakete (nicht Teil dieses Konzepts)

- **Befüllungsmultiedit im Kalender:** Zellen (Tag×Raum) anklicken → Terminerstellung im Bulk. Muster existiert im Schichtplan (`ShiftPlan.vue`: `multiEditModeCalendar` + `multiEditCalendarDays` → Modal im Multi-Modus). Grobe Schätzung: 6–10h inkl. Termin-Modal-Anpassung.
- **Echtes Monatsraster (Mo–So):** klassische Monatsansicht ohne Raumspalten, Räume als Filter/Farbmarker. Nur falls das Zoom-Preset „Monatsansicht" in der Praxis nicht reicht.
- **Drag-Resize pro Raumspalte:** individuelle Breite je Raum, gespeichert pro User+Raum.
