# Offene Bugs & Verbesserungspunkte — Übersicht (Stand 04.07.2026, nach Fix-Runde)

> Abgleich der Audits gegen den Code + **Fix-Runde vom 04.07.2026** auf Branch
> `fix/audit-offene-punkte` (Commit f6dc006b5, basiert auf origin/dev).
> Suite: 2132 Tests grün · `npm run build` grün · Fixes vor Umsetzung einzeln am Code re-verifiziert.

## ✅ In dieser Runde GEFIXT (Commit f6dc006b5)

**Inventar/Materialausgabe:** I4 (Upload-Validierung), I5 (Soft-Delete/Restore-Bestand), I6+I34 (count-Validierung, 0-Mengen, trashed Events, Transaktion), I8 (match-default-Arme — war entgegen früherer Meldung nur teilgefixt), I9 (Basket-ID-1-Hardcode), I10 (Korb-Leeren erst nach Speichern), I11 (updateField-Validierung), I12 (Subkategorie entfernbar), I13 (letzter Artikel entfernbar), I14 (alle Buchungen statt erster — keine Geister-Buchungen), I15 (Rücknahme bucht zurück), I17 (Restore-Scope), I18 (Hauptbild-Flag), I19 (crm_contacts-Join), I20 (usageData-Validierung + Zeitraum-Kappung), I23/I24 (exists ohne Trashed, Issue-Transaktionen), I25 (Log: gelöschte Issues sichtbar, Projektfilter dicht, Datums-Validierung), I26 (Überbuchungsanzeige: Tages-Expansion + Zeitüberlappung), I27 (Migration verwirft keine NUMBER-Properties mehr — Deployment-kritisch), I29 (richtiger Detailartikel bei aktiver Suche), I31 (Warenkorb-Mengen numerisch), I32 (per_page-Cap), I36 (Nummern-Generierung transaktional), I50+N8 (echter Preset-Test + exists-Regeln, 3 neue Regressionstests).

**Notifications/Tasks:** NC3 (Self-Notification), NC4 (Upsert-Notifications löschen), C3 (Deadline-Spam — neues additives Flag `sent_deadline_tomorrow_notification`, Migration `2026_07_04_000000`).

**Schicht:** S2 (Quali-Löschen bei trashed Schichten + Transaktion), S3 (leere catch-Blöcke → Logging + Fehler-Flash, neue Übersetzungen), S4 (end_date-Null-Guard).

**Projekt/CRM/Budget/Sage:** P2 (duplicate kopiert Pivot-Flags), P7 (Manager-Sync erhält Flags), CR2 (CRM-Daten-Leak: nur sichtbare Property-Werte im Payload + Filter-Orakel dicht), F4 (bcadd statt Float — Payload-Format unverändert), F7 (moveSageDataRow Guard + deterministische Reihenfolge), F8/N5 (Sage-Regular-Import pro Buchung transaktional).

**Bei der Verifikation als bereits erledigt festgestellt (kein Fix nötig):** U5 (Routen nutzen `{vacation}`), X7 (Issue-Services nutzen konsistent `disk('public')`), F5 (linked_type wird respektiert), Issue-fileDelete (Routen sind `can:inventory.disposition`-gated; Issues haben keine User-Ownership), C6, P3, L1 (aus voriger Runde).

## 🔲 Weiterhin offen

### Produktentscheidungen (bewusst nicht ohne Rückfrage umgesetzt)
- **I16** Überbuchungs-Benachrichtigung: rechnet ohne Zeitüberlappung, externe Issues fehlen, feuert im Edit-Fluss kaum. Empfehlung: auf `calculatePeakConcurrentUsage` umstellen + Change-Detection im updateField-Pfad. (Rework der Notification-Logik — Verhalten ändert sich spürbar.)
- **I42** Verfügbarkeit hängt am Status-NAMEN `'Einsatzbereit'` (mind. 3 Vue-Stellen + Backend). Empfehlung: Flag `is_available_status` am Status (Migration + UI).
- **CR9/CR10 + A3** Contacts-/Accommodation-/RoomType-CRUD ohne `can:`-Gate — es gibt kein eindeutiges Frontend-Gate zum Spiegeln; erst Rechtekonzept klären (Prinzip: Backend-Recht == Frontend-Gate), sonst sperrt man legitime Nutzer aus. CRM-write-back per Property-NAME (bricht bei Umbenennung) → Design-Thema.
- **I36-Rest** Inventarnummern-Recycling nach ForceDelete (Etiketten!): braucht Sequenz-Tabelle oder withTrashed-Max — Produktentscheidung.
- **N4/L4** Fehlende FKs (`inventory_article_status_id`, `type_of_room` als varchar): DB-Migrationen auf Live-Daten mit Orphan-Risiko — nur koordiniert mit Prod-Datenprüfung. Code-seitige Guards existieren jetzt (exists-Regeln).
- **F3** Drei divergierende „Skip-3-Spalten"-Semantiken: welche Semantik die richtige ist (position vs. sortKeys vs. groupBy), muss fachlich entschieden werden. F4 hat die Float-Drift-Komponente bereits beseitigt.
- **Sage N4** `unique('sub_position_row_id')` verwirft Mehrfach-Verknüpfungen (unsicher, ob gewollt).

### Kleinere offene Inventar-Punkte (P2-Rest aus dem Audit)
I33 (Store-Request-Lücken teilw.), I35 (NULL-Enden-Semantik Grid vs. Panel), I37 (Warenkorb-Unique-Constraint), I38 (Filterpfad-Inkonsistenz), I39 (LIKE-Wildcard-Escape), I40/I41 (Legacy-Kaskaden/Zellwert-Validierung), I43–I49 (Frontend-Kleinkram: Pagination-Reset, Race im Side-Panel, Modal-Details, Datumsformat, i18n).

### Pflichtenheft (Feature-Arbeit, kein Bug)
1. **DP-04/2.7 + DP-19**: 3-Monats-Wochentags-Durchschnitt (`WorkingHourService`).
2. **DP-04/2.17**: `FreeSundayCombinationCheck` (Sa/Mo-Kombination).
3. **DP-05 (Rest)**: Warn-/Fehlerstufen je Regel + Modal Info/Aktion-Tab-Split.
4. **DP-15**: Multi-Gewerk-Commit im `ShiftCommitDateSelectModal`.
5. **MAT-05/1.27**: Tab-Split — wartet auf Auftraggeber-Rückmeldung.
6. **DP-21-Caveat**: Recht „unveröffentlichte Dienste sehen" — klären ob gefordert.

### Performance (Analyse 02.–04.07.)
- HTTP/2 + Redis auf Prod-Server (Server-Config, größter Hebel).
- ShiftPlan-Worker-Endpoint-Pagination · tabler-icons-Bundle (2,6 MB) splitten · Crafts-users-Payload in `viewShiftPlan`.

### Repo/Prozess
- Branch `perf/ladezeiten-stufe-1-2` (3 Commits, getestet) **und** Branch `fix/audit-offene-punkte` (dieser Commit) sind ungepusht → pushen + MRs gegen `dev`.
- Beim Deployment dieser Runde: 1 neue Migration (`tasks`-Spalte, additiv, unkritisch).
- Finaler Klick-Test der Auth-Nachrüstung mit echten Nicht-Admin-Rollen steht weiter aus.
- `test_checklist_fix.php` / `test_checklist_service_fix.php` im Root sind tote Skripte → entfernen.
