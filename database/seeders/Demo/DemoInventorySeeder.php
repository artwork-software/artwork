<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Artwork\Modules\Room\Models\Room;
use Database\Seeders\Demo\Support\DemoInventoryPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Inventar: Eigenschafts-Katalog mit Kategorie-Zuordnung, ~100 Artikel mit
 * echten Gerätenamen, kuratierten Fundus-Fotos, Einzelbestand mit
 * Seriennummern und praxisnaher Varianz in Zustand, Prüfterminen und
 * Beständen. Weitere Artikelbilder werden manuell gepflegt. Additiv &
 * idempotent: vorhandene Werte werden nie überschrieben, nur fehlende ergänzt.
 */
class DemoInventorySeeder extends Seeder
{
    private const ZUSTAND_WEIGHTS = ['Neuwertig' => 15, 'Gut' => 55, 'Gebrauchsspuren' => 25, 'Reparaturbedürftig' => 5];

    /** Wahrscheinlichkeit "Verleihbar" je Kategorie (Default 0.5). */
    private const VERLEIHBAR_CHANCE = [
        'Licht' => 0.6, 'Ton' => 0.6, 'Video' => 0.5, 'Bühne & Rigging' => 0.55,
        'Kostüm & Requisite' => 0.25, 'Transport & Cases' => 0.4, 'Werkstatt & Werkzeug' => 0.1,
    ];

    /** @var array<string, InventoryArticleProperties> */
    private array $propertyByName = [];

    /** @var array<string, int|null> */
    private array $manufacturerIds = [];

    /** @var array<string, int|null> */
    private array $roomIdByCategory = [];

    public function run(): void
    {
        $random = new DemoRandom('inventory');

        $this->ensureProperties();

        $readyStatus = InventoryArticleStatus::query()->where('name', 'Einsatzbereit')->first()
            ?? InventoryArticleStatus::query()->where('default', true)->first();
        $brokenStatus = InventoryArticleStatus::query()
            ->where('name', 'like', '%Reparatur%')
            ->orWhere('name', 'like', '%efekt%')
            ->first();

        $createdArticles = 0;
        foreach (DemoInventoryPools::INVENTORY as $categoryName => $subCategories) {
            $category = InventoryCategory::firstOrCreate(['name' => $categoryName]);
            $this->assignCategoryProperties($category, DemoInventoryPools::CATEGORY_PROPERTIES[$categoryName] ?? []);

            foreach ($subCategories as $subCategoryName => $articles) {
                $subCategory = InventorySubCategory::firstOrCreate(
                    ['name' => $subCategoryName, 'inventory_category_id' => $category->id]
                );
                $this->assignCategoryProperties(
                    $subCategory,
                    DemoInventoryPools::SUB_CATEGORY_PROPERTIES[$categoryName . '|' . $subCategoryName] ?? []
                );

                foreach ($articles as $definition) {
                    $createdArticles += $this->seedArticle(
                        $definition,
                        $category,
                        $subCategory,
                        $random->fork($definition['name']),
                        $readyStatus,
                        $brokenStatus
                    );
                }
            }
        }

        $repaired = $this->repairManufacturerValues();

        $this->command?->info(sprintf(
            'Inventar: %d Artikel neu angelegt, %d Hersteller-Werte repariert.',
            $createdArticles,
            $repaired
        ));
    }

    /* -----------------------------------------------------------------
     | Eigenschaften & Kategorie-Zuordnung
     | ----------------------------------------------------------------- */

    private function ensureProperties(): void
    {
        // Systemeigenschaften über den Typ auflösen (Namen sind übersetzbar/änderbar)
        $room = InventoryArticleProperties::query()->where('type', 'room')->first();
        $manufacturer = InventoryArticleProperties::query()->where('type', 'manufacturer')->first();
        if ($room !== null) {
            $this->propertyByName['Raum'] = $room;
        }
        if ($manufacturer !== null) {
            $this->propertyByName['Hersteller'] = $manufacturer;
        }

        $order = (int) InventoryArticleProperties::query()->max('order');
        foreach (DemoInventoryPools::PROPERTIES as $name => $definition) {
            $property = InventoryArticleProperties::firstOrCreate(
                ['name' => $name],
                [
                    'type' => $definition['type'],
                    'select_values' => $definition['select_values'] ?? null,
                    'is_filterable' => $definition['is_filterable'] ?? false,
                    'show_in_list' => $definition['show_in_list'] ?? false,
                    'tooltip_text' => $definition['tooltip_text'] ?? null,
                    'is_required' => false,
                    'is_deletable' => true,
                    'across_articles' => $definition['across_articles'] ?? false,
                    'individual_value' => $definition['individual_value'] ?? false,
                    'order' => ++$order,
                ]
            );
            // Bestandszeilen: nur leere Auswahllisten nachfüllen, Flags nicht anfassen
            if (
                !$property->wasRecentlyCreated
                && empty($property->select_values)
                && !empty($definition['select_values'])
            ) {
                $property->update(['select_values' => $definition['select_values']]);
            }
            $this->propertyByName[$name] = $property;
        }
    }

    /**
     * Weist einer (Sub-)Kategorie die Eigenschaften in Pool-Reihenfolge zu.
     *
     * @param array<string> $propertyNames
     */
    private function assignCategoryProperties(Model $categoryOrSub, array $propertyNames): void
    {
        foreach ($propertyNames as $position => $name) {
            $property = $this->propertyByName[$name] ?? null;
            if ($property === null) {
                continue;
            }
            $exists = $categoryOrSub->properties()
                ->where('inventory_article_properties.id', $property->id)
                ->exists();
            if ($exists) {
                $categoryOrSub->properties()->updateExistingPivot($property->id, ['position' => $position]);
            } else {
                $categoryOrSub->properties()->attach($property->id, ['value' => '', 'position' => $position]);
            }
        }
    }

    /* -----------------------------------------------------------------
     | Artikel
     | ----------------------------------------------------------------- */

    /** @param array<string, mixed> $definition */
    private function seedArticle(
        array $definition,
        InventoryCategory $category,
        InventorySubCategory $subCategory,
        DemoRandom $rng,
        ?InventoryArticleStatus $readyStatus,
        ?InventoryArticleStatus $brokenStatus
    ): int {
        $isDetailed = (bool) ($definition['detailed'] ?? false);
        $article = InventoryArticle::firstOrCreate(
            ['name' => $definition['name']],
            [
                'description' => 'Demo-Artikel des Artwork Testhauses.',
                'inventory_category_id' => $category->id,
                'inventory_sub_category_id' => $subCategory->id,
                'quantity' => $definition['quantity'],
                'is_detailed_quantity' => $isDetailed,
            ]
        );

        $this->ensureImage($article, $definition['image'] ?? null);

        $values = $this->buildPropertyValues($definition, $category->name, $subCategory->name, $rng->fork('props'));

        if ($article->is_detailed_quantity) {
            $this->ensureDetailedArticles($article, $definition, $rng->fork('detail'), $readyStatus, $brokenStatus);
            $this->fillDetailedProperties($article, $values, $rng->fork('detailprops'));
            // Raum/Hersteller zusätzlich am Artikel selbst (Suche + Anzeige-Fallback)
            foreach (['Raum', 'Hersteller'] as $name) {
                if (isset($values[$name])) {
                    $this->setPropertyValueIfMissing($article, $name, $values[$name]);
                }
            }
        } else {
            foreach ($values as $name => $value) {
                $property = $this->propertyByName[$name] ?? null;
                if ($property === null || $property->individual_value) {
                    continue;
                }
                $this->setPropertyValueIfMissing($article, $name, $value);
            }
            // Nicht-Einzelbestand: Zustand gilt für den Gesamtposten
            if (isset($values['Zustand'])) {
                $this->setPropertyValueIfMissing($article, 'Zustand', $values['Zustand']);
            }
            if (isset($values['Nächste Prüfung'])) {
                $this->setPropertyValueIfMissing($article, 'Nächste Prüfung', $values['Nächste Prüfung']);
            }
            $this->ensureStatusValues($article, $rng->fork('status'), $readyStatus, $brokenStatus);
        }

        return $article->wasRecentlyCreated ? 1 : 0;
    }

    /**
     * Baut die Soll-Werte für alle der Kategorie zugeordneten Eigenschaften.
     *
     * @param array<string, mixed> $definition
     * @return array<string, string>
     */
    private function buildPropertyValues(
        array $definition,
        string $categoryName,
        string $subCategoryName,
        DemoRandom $rng
    ): array {
        $assigned = array_merge(
            DemoInventoryPools::CATEGORY_PROPERTIES[$categoryName] ?? [],
            DemoInventoryPools::SUB_CATEGORY_PROPERTIES[$categoryName . '|' . $subCategoryName] ?? []
        );
        $fixed = $definition['props'] ?? [];
        $values = [];

        foreach (array_unique($assigned) as $name) {
            if (isset($fixed[$name])) {
                $values[$name] = (string) $fixed[$name];
                continue;
            }
            $value = match ($name) {
                'Raum' => $this->resolveRoomId($categoryName),
                'Hersteller' => $this->resolveManufacturerId($definition['manufacturer'] ?? null),
                'Zustand' => (string) $rng->weighted(self::ZUSTAND_WEIGHTS),
                'Anschaffungsjahr' => (string) $rng->int(2009, 2025),
                'Nächste Prüfung' => Carbon::now()->addDays($rng->int(-30, 420))->format('Y-m-d'),
                'Verleihbar' => $rng->chance(self::VERLEIHBAR_CHANCE[$categoryName] ?? 0.5) ? '1' : '0',
                default => null,
            };
            if ($value !== null && $value !== '') {
                $values[$name] = $value;
            }
        }

        // Seriennummern werden je Einzelstück generiert, nicht hier
        unset($values['Seriennummer']);

        return $values;
    }

    /**
     * Legt fehlende Einzelbestands-Zeilen an (nur wenn noch keine existieren,
     * wie bei allen Demo-Seedern: vorhandene Bestände bleiben unangetastet).
     *
     * @param array<string, mixed> $definition
     */
    private function ensureDetailedArticles(
        InventoryArticle $article,
        array $definition,
        DemoRandom $rng,
        ?InventoryArticleStatus $readyStatus,
        ?InventoryArticleStatus $brokenStatus
    ): void {
        if ($article->detailedArticleQuantities()->exists()) {
            return;
        }

        $quantity = (int) $definition['quantity'];
        $brokenIndex = $brokenStatus !== null && $rng->chance(0.6) ? $rng->int(0, $quantity - 1) : -1;
        $inventoryBase = $rng->int(10000, 99999);
        for ($i = 0; $i < $quantity; $i++) {
            $article->detailedArticleQuantities()->create([
                'external_id' => sprintf('demo-%d-%02d', $article->id, $i + 1),
                'name' => sprintf('%s #%02d', $definition['name'], $i + 1),
                'description' => $i === $brokenIndex ? 'In Reparatur (Demo).' : '',
                'quantity' => 1,
                'inventory_number' => sprintf('TH-%05d-%02d', $inventoryBase, $i + 1),
                'inventory_article_status_id' => $i === $brokenIndex
                    ? $brokenStatus->id
                    : $readyStatus?->id,
            ]);
        }
    }

    /**
     * Backfillt Eigenschaftswerte auf allen Einzelstücken: gemeinsame Werte
     * identisch, individuelle (Zustand, Seriennummer, Prüfung) je Exemplar.
     *
     * @param array<string, string> $values
     */
    private function fillDetailedProperties(InventoryArticle $article, array $values, DemoRandom $rng): void
    {
        $serialPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $article->name) ?: 'TH', 0, 3));

        foreach ($article->detailedArticleQuantities()->get() as $index => $detailed) {
            $itemRng = $rng->fork((string) ($detailed->external_id ?? $detailed->id));

            foreach ($values as $name => $value) {
                $property = $this->propertyByName[$name] ?? null;
                if ($property === null) {
                    continue;
                }
                if ($property->individual_value) {
                    $value = match ($name) {
                        'Zustand' => (string) $itemRng->weighted(self::ZUSTAND_WEIGHTS),
                        'Nächste Prüfung' => Carbon::now()->addDays($itemRng->int(-30, 420))->format('Y-m-d'),
                        default => $value,
                    };
                }
                $this->setPropertyValueIfMissing($detailed, $name, $value);
            }

            if (isset($this->propertyByName['Seriennummer'])) {
                $serial = sprintf('%s-%07d', $serialPrefix, $itemRng->int(1000000, 9999999));
                $this->setPropertyValueIfMissing($detailed, 'Seriennummer', $serial);
            }
        }
    }

    /**
     * Bestands-Status: alles einsatzbereit, gelegentlich ein kleiner
     * Defekt-Anteil. Bestehende Statuswerte werden nie verändert.
     */
    private function ensureStatusValues(
        InventoryArticle $article,
        DemoRandom $rng,
        ?InventoryArticleStatus $readyStatus,
        ?InventoryArticleStatus $brokenStatus
    ): void {
        if ($readyStatus === null || $article->statusValues()->exists()) {
            return;
        }

        $quantity = (int) $article->quantity;
        $broken = 0;
        if ($brokenStatus !== null && $quantity >= 4 && $rng->chance(0.25)) {
            $broken = $rng->int(1, max(1, (int) floor($quantity * 0.08)));
        }

        $article->statusValues()->attach($readyStatus->id, ['value' => (string) ($quantity - $broken)]);
        if ($broken > 0) {
            $article->statusValues()->attach($brokenStatus->id, ['value' => (string) $broken]);
        }
    }

    /* -----------------------------------------------------------------
     | Werte-Helfer
     | ----------------------------------------------------------------- */

    /**
     * Schreibt einen Eigenschaftswert nur, wenn noch keiner gepflegt ist.
     * Hersteller-Werte müssen CRM-Kontakt-IDs sein — alte Seeder-Stände mit
     * Namens-Strings oder Leerwerten werden dabei repariert.
     */
    private function setPropertyValueIfMissing(Model $owner, string $propertyName, string $value): void
    {
        $property = $this->propertyByName[$propertyName] ?? null;
        if ($property === null) {
            return;
        }

        $existing = $owner->properties()
            ->where('inventory_article_properties.id', $property->id)
            ->first();

        if ($existing === null) {
            $owner->properties()->attach($property->id, ['value' => $value]);

            return;
        }

        $current = $existing->pivot->value;
        $isEmpty = $current === null || trim((string) $current) === '';
        $isBrokenManufacturer = $property->type === 'manufacturer' && !ctype_digit((string) $current);
        if ($isEmpty || $isBrokenManufacturer) {
            $owner->properties()->updateExistingPivot($property->id, ['value' => $value]);
        }
    }

    /**
     * Altbestand der ersten Seeder-Runde: Hersteller-Werte waren Namens-Strings
     * statt CRM-Kontakt-IDs (die Übersicht zeigt dann nichts an). Auflösbare
     * Namen werden auf die Kontakt-ID umgeschrieben, der Rest gelöscht.
     */
    private function repairManufacturerValues(): int
    {
        $manufacturerProperty = $this->propertyByName['Hersteller'] ?? null;
        if ($manufacturerProperty === null) {
            return 0;
        }

        $broken = DB::table('inventory_property_values')
            ->where('inventory_article_property_id', $manufacturerProperty->id)
            ->whereRaw("value NOT REGEXP '^[0-9]+$'")
            ->get();

        $repaired = 0;
        foreach ($broken as $row) {
            $name = trim((string) $row->value);
            $contactId = $name === '' ? null : CrmContact::query()
                ->where('display_name', $name)
                ->orderBy('id')
                ->value('id');
            if ($contactId !== null) {
                DB::table('inventory_property_values')
                    ->where('id', $row->id)
                    ->update(['value' => (string) $contactId]);
            } else {
                DB::table('inventory_property_values')
                    ->where('id', $row->id)
                    ->delete();
            }
            $repaired++;
        }

        return $repaired;
    }

    private function resolveRoomId(string $categoryName): ?string
    {
        if (!array_key_exists($categoryName, $this->roomIdByCategory)) {
            $id = null;
            foreach (DemoInventoryPools::CATEGORY_ROOMS[$categoryName] ?? ['Lager Technik'] as $roomName) {
                $id = Room::query()->where('name', $roomName)->value('id');
                if ($id !== null) {
                    break;
                }
            }
            $this->roomIdByCategory[$categoryName] = $id;
        }

        return $this->roomIdByCategory[$categoryName] !== null
            ? (string) $this->roomIdByCategory[$categoryName]
            : null;
    }

    private function resolveManufacturerId(?string $manufacturerName): ?string
    {
        if ($manufacturerName === null || $manufacturerName === '') {
            return null;
        }
        if (!array_key_exists($manufacturerName, $this->manufacturerIds)) {
            $type = CrmContactType::firstOrCreate(
                ['slug' => CrmSystemContactTypeEnum::MANUFACTURER->value],
                [
                    'name' => 'Hersteller*in',
                    'icon' => 'IconBuildingFactory2',
                    'color' => '#64748b',
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => (int) CrmContactType::query()->max('sort_order') + 1,
                ]
            );
            $contact = CrmContact::query()
                ->where('crm_contact_type_id', $type->id)
                ->where('display_name', $manufacturerName)
                ->first()
                ?? CrmContact::create([
                    'crm_contact_type_id' => $type->id,
                    'display_name' => $manufacturerName,
                    'is_active' => true,
                ]);
            $this->manufacturerIds[$manufacturerName] = $contact->id;
        }

        return $this->manufacturerIds[$manufacturerName] !== null
            ? (string) $this->manufacturerIds[$manufacturerName]
            : null;
    }

    /* -----------------------------------------------------------------
     | Bilder
     | ----------------------------------------------------------------- */

    /**
     * Hängt ein kuratiertes Fundus-Foto an den Artikel (einmalig). Artikel
     * ohne kuratiertes Foto bleiben bewusst ohne Bild — Bilder werden
     * manuell gepflegt.
     */
    private function ensureImage(InventoryArticle $article, ?string $curatedFile): void
    {
        if ($curatedFile === null || $article->images()->exists()) {
            return;
        }

        $sourcePath = __DIR__ . '/assets/fundus/' . $curatedFile;
        if (!is_file($sourcePath)) {
            return;
        }
        $targetPath = 'inventory_articles/demo_' . $curatedFile;
        Storage::disk('public')->put($targetPath, (string) file_get_contents($sourcePath));
        $this->createImageRow($article, $targetPath);
    }

    private function createImageRow(InventoryArticle $article, string $targetPath): void
    {
        $thumbnail = app(InventoryArticleImageService::class)->generateThumbnail($targetPath);
        $article->images()->create([
            'image' => $targetPath,
            'thumbnail' => $thumbnail,
            'is_main_image' => true,
            'order' => 1,
        ]);
    }
}
