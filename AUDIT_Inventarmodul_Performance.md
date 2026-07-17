# Performance-Audit Inventarmodul — 2026-07-07

Grundlegende Performance-Analyse der drei Inventar-Module (`Inventory` neu, `InventoryManagement` Legacy-Craft-Inventar, `InventoryScheduling`) plus Frontend.

## Umsetzungsstand (2026-07-08)

**Umgesetzt** (safe Kern beider Hebel; 104 Inventar-Tests grün, `vite build` grün):
- **Hebel B (N+1):** Fehlende Eager Loads gegen die `$appends`-N+1 an 6 Stellen ergänzt — `getArticleList` (`detailedArticleQuantities.properties`), `getAvailabilityData` (dito), `search()` (`properties` + `detailedArticleQuantities.properties`), `getAllCategories` (`articles.properties`), `index()`-Category/SubCategory-Loads (`articles.category/subCategory/properties`), `getAllWithRelations` (articles + subcategories.articles vollständig). Response-Shape unverändert, nur Query-Zahl sinkt.
- **Hebel A (Legacy-Frontend):** A6 (5-Sek-Voll-Reload nach Sortieren → sofortiger `only:['crafts']`-Reload), A5 (`onUpdated` → gezielte `watch` auf props.crafts/craftFilters), A3 (Suche mit 250ms-Debounce).

**Noch offen** (riskantere needs-test-Posten, bewusst NICHT angefasst): A1 (`cells.column`-Payload-Duplikat — braucht koordinierten Frontend+Export-Umbau), A2 (Partial-Reloads pro Zell-Mutation), A4 (`ref(craft)` entfernen), B2 (Payload-Diät/Such-Dedup), sowie alle Prio-2/3-Posten unten außer den o.g.

**Methodik:** 3 parallele Code-Analysen (Backend neu / Backend Legacy+Scheduling / Frontend), Kernbehauptungen gegen die lokale DB-Schema verifiziert. Die lokale Dev-DB ist klein (40 Artikel, Legacy-Tabellen leer) — alle Aussagen sind Skalierungsanalysen des Codes, keine Messungen. Auf Prod ist das Legacy-Craft-Inventar laut Inventar-Audit lebendig.

**Bug-Audit-Abgrenzung:** Funktionale Bugs stehen in `AUDIT_Inventarmodul_Bugs.md` (I1–I50). Hier nur Performance.

---

## Wichtigster Kontext-Befund zuerst

**Der Legacy-Scheduling-Lesepfad ist toter Code.** `CraftService::getCraftsWithInventory()` → `getItemEvents()` → Overbooked-Berechnung (inkl. der uncommitted Änderungen in `CraftInventoryItemEventService.php`) hat **keinen Produktions-Aufrufer** im Repo — nur Tests. Live sind nur die Schreib-Endpunkte (drop/update/destroy/storeMultiple) und die Event-Lifecycle-Hooks im `EventController` (billig: 1 SELECT + 1 UPDATE/DELETE pro Buchung). Perf-Befunde in diesem Pfad (PS-1, PS-2 unten) sind daher Vorsorge, kein akuter Handlungsbedarf.

---

## Priorität 1 — Die zwei großen Hebel

### A) Legacy-Craft-Inventar-Hauptseite (`/inventory-management`): Payload × Reload × Client-Verarbeitung multiplizieren sich

Die Baum-Ladung selbst ist **korrekt eager-geladen** (~10–12 Queries, kein N+1). Das Problem ist die Kette danach:

| # | Befund | Wo | Risiko des Fixes |
|---|---|---|---|
| A1 | Kompletter Baum (Crafts→Kategorien→Gruppen→Ordner→Items→Zellen) unpaginiert in einem Inertia-Payload; **`cells.column` dupliziert das volle Spalten-Objekt pro Zelle** (N Items × C Spalten), obwohl `columns` als eigenes Prop mitkommt. 2.000 Items × 8 Spalten ≈ mehrere MB JSON | `artwork/Modules/Craft/Services/CraftService.php:50,52` (Eager-Load `…cells.column`), `InventoryController.php:44-52` | needs-test (Frontend-Zugriffe auf `cell.column` prüfen) |
| A2 | **Jede Mutation lädt den ganzen Baum neu**: Zell-Edit, Umbenennen, Drag&Drop → `router.patch` ohne `only:` → `Redirect::back()` → voller Baum-Rebuild + MB-Transfer pro Interaktion. Kein einziges `only:` im Legacy-Verzeichnis | `resources/js/Pages/Inventory/InventoryManagement/InventoryItemCell.vue:239-250,268,299` u. a. | needs-test |
| A3 | **Deep-Clone + Voll-Scan bei jedem Keystroke, ohne Debounce**: `filteredCrafts` startet mit `JSON.parse(JSON.stringify(crafts))` über den ganzen Baum; Suche emittet ungedrosselt; Umlaut-Matching macht pro Zelle 3 `.replace` + 4 `.includes` | `resources/js/Pages/Inventory/Composeables/useCraftFilterAndSearch.js:26` + `InventoryTopBar.vue` | Debounce: **safe**; Clone-Ersatz: needs-test |
| A4 | `ref(craft)` pro Craft im computed → jeder Recompute erzeugt neue Objekt-Identitäten → Vue patcht den kompletten Komponentenbaum neu | `useCraftFilterAndSearch.js:126-130` | needs-test |
| A5 | `onUpdated(() => setFilterAndSearchData())` synct Props bei **jedem** Root-Update (auch Header-Hover) und stößt damit A3 erneut an; dazu `getCraftFilters()` baut pro Render ein neues Array | `Inventory.vue:449-455,25` | **safe** (durch `watch` ersetzen) |
| A6 | Sortier-Klick plant `setTimeout(5000) → router.reload()` (alle Props) **zusätzlich** zum Full-Reload des Patches — doppelter Voll-Payload, Reload kann mitten in Interaktion fallen | `Inventory.vue:440-444` | **safe** |
| A7 | Zellen-Edit schreibt Editor-JSON-Blob (E-Mail, Telefon, Foto-URL, ~0,5–1 KB) in die Last-Edit-Zelle **jedes Items** → bläht dauerhaft jeden Index-Payload auf. 4 Queries pro Zell-Edit | `CraftInventoryItemCellService.php:44-86` | needs-test (Frontend parst das JSON) |

**Empfohlene Reihenfolge:** A5+A6 (safe, sofort) → A3-Debounce (safe) → A1 `cells.column` raus (größter Payload-Hebel) → A2 partial reloads → A4/A3-Clone (zusammen angehen).

### B) Neues Inventarmodul: `$appends`-N+1-Kaskaden × überladene Index-Payload

| # | Befund | Wo | Risiko |
|---|---|---|---|
| B1 | `$appends = ['room','manufacturer','category','subCategory']` auf `InventoryArticle` (und `['room','manufacturer']` auf `InventoryDetailedQuantityArticle`) feuern bei **jeder** Serialisierung; `room`/`manufacturer`-Accessoren brauchen `properties` → lazy Query pro Artikel, wo nicht eager geladen. Betroffen (verifizierte Eager-Load-Lücken): `InventoryCategoryController::getAllCategories()` (alle Artikel, ohne `properties` → 1 Query/Artikel), `InventoryCategoryRepository::getAllWithRelations()` (auf **jeder** `inventory.index`-Seite; `subcategories.articles` ganz ohne Eager Loads → 2–3 Queries/Artikel), `search()` (bis 50 lazy Queries pro Autocomplete-Anschlag), `getArticleList()`/`getAvailabilityData()`/`indexTrash` (fehlendes `detailedArticleQuantities.properties`) | `InventoryArticle.php:68`, `InventoryDetailedQuantityArticle.php:35`, `InventoryCategoryController.php:228-242,48-67`, `InventoryCategoryRepository.php:33-60`, `InventoryArticleController.php:258-263,81-92`, `InventoryArticleService.php:59-71`, `InventoryPlanningService.php:44-50` | Eager Loads ergänzen: **safe**. Mittelfristig `$appends` → DTO/Resource: needs-test |
| B2 | `inventory.index` schickt den Gesamtbestand mehrfach: paginierte `articles` (ok) + `categories` mit **allen** Artikeln + Bildern + `tags` UND `tagGroups.tags` doppelt, jeweils mit vollen `allowedUsers`-User-Models (inkl. User-Appends wie `profile_photo_url`) + `currentCategory` nochmal mit allen Artikeln. Zusätzlich läuft die Suche doppelt (`getArticleList` + `getCountsByStatusAggregated` rufen beide `buildArticleQuery()` → 2 Meilisearch/Scout-Roundtrips pro Request) | `InventoryCategoryController.php:115-159`, `InventoryArticleService.php:57,250` | Such-Dedup: safe; Payload-Diät: needs-test (Frontend-Props) |
| B3 | Planungsseite shared **alle** Projekte und **alle** User pro Request (User-`$appends` serialisieren `profile_photo_url`, `formated_work_time_balance` etc. mit) + `groupedArticles` = alle gefilterten Artikel als volle Models | `InventoryUserFilterShareService.php:72-86`, `InventoryPlanningService.php:44-50` | needs-test |

Geschätzte Wirkung B1+B2: Bei ~1.000 Artikeln aktuell grob 2.000–3.000 Queries + mehrere MB JSON pro `inventory.index`-Aufruf; nach Fix ~20–30 Queries + Payload proportional zur Seitengröße.

---

## Priorität 2 — Sichere Quick-Wins (geringes Risiko, klarer Nutzen)

| # | Befund | Wo | Fix |
|---|---|---|---|
| Q1 | **Fehlende Indizes** (gegen lokale DB verifiziert): `inventory_detailed_quantity_articles.inventory_article_status_id` und `inventory_article_status_values.inventory_article_status_id` sind unindiziert → Status-Filter/-Aggregation scannen. *Achtung: `inventory_article_id` ist über den Composite-Unique `(inventory_article_id, detail_number)` bereits abgedeckt — dort ist NICHTS zu tun.* | Squash-Schema | hasIndex-guarded Migration (Muster `2026_07_07_restore_squashed…`), **safe** |
| Q2 | `checkAndNotifyOverbooking`: identische SUM-Query pro Issue in Schleife statt einmal davor; dazu lazy `responsibleUsers` pro Issue (auch in `notifyResponsibleUsersOnArticleChange`) | `InventoryArticleService.php:568-574,605-610,492-496` | Summe vorziehen + `with('responsibleUsers')`, **safe** |
| Q3 | `applyFilters`: `InventoryArticleProperties::find()` pro Filtereintrag, läuft pro Index-Request 2× | `InventoryArticleRepository.php:81-82` | `whereIn(...)->keyBy('id')`, **safe** |
| Q4 | Filter/Preset-Aktionen der neuen Artikel-Übersicht machen `router.reload()` **ohne `only:`** (die Suche daneben macht es richtig mit `only:['articles','countsByStatus']`) | `InventoryFilterComponent.vue:701-707,719-725,816-820,831,845,976` | `only:` ergänzen, **safe** |
| Q5 | Artikel-Grid ohne `:key` + `findBasketForArticle` linear 2× pro Karte pro Render | `Index.vue:83,89,91`, `InventorySingleArticleInTable.vue:40` | `:key="item.id"` + Map-Lookup, **safe** |
| Q6 | Spalte anlegen/duplizieren (Legacy): lädt **alle** Items als Models, 1 INSERT pro Item (5.000 Items = 5.000 INSERTs in einem Web-Request) | `CraftInventoryItemService.php:52-64` | `pluck('id')` + Bulk-`insert()`, **safe** (keine Model-Events auf der Zelle) |
| Q7 | Reorder-Endpunkte (Items/Kategorien/Gruppen/Spalten/Ordner): 1–2 Queries pro Zeile, auch für unveränderte | `CraftInventoryItemService.php:69-87`, `CraftInventoryCategoryService.php:52-69`, `CraftInventoryGroupService.php:52-71`, `CraftsInventoryColumnService.php:154-160` | mind. nur Geänderte updaten (`if order !== index`), **safe** |
| Q8 | `storeMultiple` (Scheduling, live!): 1 SELECT pro Event + 1 INSERT pro Item×Event (Multi-Edit-Modal, 50×20 = 1.050 Queries) | `CraftInventoryItemEventService.php:227-258` | Events `whereIn`-batchen + Bulk-Insert, **safe/needs-test** |
| Q9 | Toter Cache-Key: `delete()` invalidiert `inventory_article_count`, den nie jemand befüllt | `InventoryArticleService.php:811,179-182` | `Cache::remember` in `count()` + Invalidierung auch in store/restore/forceDelete, needs-test (Invalidierungspfade) |

---

## Priorität 3 — Größere Umbauten (needs-test / Produktentscheidung)

| # | Befund | Wo |
|---|---|---|
| U1 | Legacy-Export: Frontend POSTet den **gesamten gefilterten Baum als JSON** zurück (MB-Upload), 4-Ebenen-Wildcard-Validierung materialisiert pro Element, Baum landet im file-Cache, Export via `FromView` (langsamster PhpSpreadsheet-Pfad) komplett im Speicher → memory_limit-Risiko | `SelectExportTypeModal.vue:65-88`, `InventoryManagementExportController.php:29-75`, `InventoryManagementExport.php`. Fix: nur `craftIds`+Filter senden, Baum serverseitig bauen; mittelfristig `FromArray` |
| U2 | `updateTypeOptions` (Select-Option entfernen): lädt alle Zellen der Spalte, 4 Queries pro betroffener Zelle | `CraftsInventoryColumnService.php:115-138`. Fix: ein `whereIn('cell_value',…)->update()`; Last-Edit-Stempel-Verhalten = Produktfrage |
| U3 | `TypeNumberGenerator`: `MAX(CAST(inventory_number AS UNSIGNED))` mit `lockForUpdate()` über die ganze Tabelle bei jedem Create (via `saving`-Hook, bei Import pro Zeile → quadratisch); serialisiert alle Creates | `TypeNumberGenerator.php:27-38`. Fix: Counter-Tabelle — fachlich sensibel, needs-test |
| U4 | `search()`-API gibt volle Models inkl. aller Bilder zurück; `properties` fehlt im `load()` (→ B1-N+1 pro Treffer) | `InventoryArticleController.php:258-263`. Eager Load safe, Antwort-Shape needs-test |
| U5 | `forceDeleteAll`: pro Artikel mehrere Queries + pro Datei-Pfad ein Full-Scan auf unindizierter `inventory_property_values.value` | `InventoryArticleController.php:145-150`, `InventoryArticleRepository.php:294-373` |
| U6 | Room-/Manufacturer-Filter joinen `inventory_property_values.value` (varchar) gegen `rooms.id` (bigint) → impliziter Cast verhindert Index-Nutzung. UNSICHER wie teuer real (MySQL-Planwahl) | `InventoryArticleRepository.php:91,106,149,164`. Fix: Filter vorab in IDs auflösen |
| U7 | Caching-Chancen: Statuses, Properties, Kategorie-Baum, Tags/TagGroups werden auf jeder Inventar-Seite komplett neu selektiert. Achtung Projektregel: file-Cache ist mit laufender App geteilt | diverse |
| U8 | Planning-Timeline: `cellValue(date)` 4× pro Zelle im Template; nach jedem Grid-Refresh re-rendern alle Rows (neue `availability`-Identität). `content-visibility: auto` mildert bereits. Fix: `rowValues`-computed (1 Lookup statt 4), safe; Virtualisierung nur falls real >200 Artikel offen | `ArticleRow.vue:16-30` |
| U9 | AddEditArticleModal: doppelte Deep-Watcher mit Selbst-Mutation → Watcher-Kaskade pro Keystroke bei Detail-Artikeln (nur im offenen Modal) | `AddEditArticleModal.vue:1455-1477,1515-1526` |
| U10 | `MaterialIssueLogController::index`: `whereNotIn` mit Sub-Select auf `activity_log` — UNSICHER ob real langsam; bei Wachstum auf `NOT EXISTS` + Composite-Index | `MaterialIssueLogController.php:73-85` |

## Vorsorge (aktuell toter Code — nur bei Reaktivierung relevant)

| # | Befund | Wo |
|---|---|---|
| PS-1 | Overbooked-Berechnung (uncommitted Stand): in-memory ok (keine Queries in Schleife), aber O(Tage × k²) durch Multi-Day-Expansion + Collection-Allokation pro Event pro Tag | `CraftInventoryItemEventService.php:112-194` |
| PS-2 | `addEventFilters`: `event.event_type` fehlt im Eager Load (N+1 pro Buchung); ungeklammertes `orWhereBetween` hebelt Item-Scoping aus (auch Korrektheitsproblem!); kein Index auf `craft_inventory_item_events.start/end` (verifiziert) | `CraftService.php:232-239` |

## Bereits gut (kein Handlungsbedarf)

- Neue Artikel-Übersicht: Suche debounced + `only:['articles','countsByStatus']`; `getArticleList` paginiert (Cap 100); `getCountsByStatusAggregated` aggregiert per SQL; API nutzt DTOs.
- Planungs-Availability: Kompakt-Deltas + einmaliges Issue-Laden (B1–B7-Optimierungen), geteilte Date-Maps, `content-visibility`, dünner JSON-Endpoint für Zell-Details.
- IssueOfMaterial-Seiten: debounced Filter + `only:['issues','articlesInFilter']`.
- Issue-Datums-Indizes existieren (Migration `2026_05_28_120000`, nach dem Squash → greift auch auf Prod).
- Legacy-Baum-Ladung selbst hat kein N+1 (sauber eager-geladen) — nur Payload/Reload-Verhalten ist das Problem.
