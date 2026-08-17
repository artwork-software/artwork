<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Artwork\Modules\Room\Models\Room;
use Database\Seeders\Demo\Support\DemoExtrasPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Inventar: Kategorien, Unterkategorien und ~40 Artikel mit echten
 * Gerätenamen, Hersteller-/Raum-Eigenschaften, Status und Detail-Mengen
 * (Seriennummern) für einige Geräte. Idempotent über Namen.
 */
class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $random = new DemoRandom('inventory');
        $manufacturerProperty = InventoryArticleProperties::query()->where('type', 'manufacturer')->first();
        $roomProperty = InventoryArticleProperties::query()->where('type', 'room')->first();
        $storageRoomId = Room::query()->where('name', 'Lager Technik')->value('id');

        $readyStatus = InventoryArticleStatus::query()->where('name', 'Einsatzbereit')->first()
            ?? InventoryArticleStatus::query()->where('default', true)->first();
        $brokenStatus = InventoryArticleStatus::query()
            ->where('name', 'like', '%Reparatur%')
            ->orWhere('name', 'like', '%efekt%')
            ->first();

        $createdArticles = 0;
        foreach (DemoExtrasPools::INVENTORY as $categoryName => $subCategories) {
            $category = InventoryCategory::firstOrCreate(['name' => $categoryName]);

            foreach ($subCategories as $subCategoryName => $articles) {
                $subCategory = InventorySubCategory::firstOrCreate(
                    ['name' => $subCategoryName, 'inventory_category_id' => $category->id]
                );

                foreach ($articles as $definition) {
                    $article = InventoryArticle::firstOrCreate(
                        ['name' => $definition['name']],
                        [
                            'description' => 'Demo-Artikel des Artwork Testhauses.',
                            'inventory_category_id' => $category->id,
                            'inventory_sub_category_id' => $subCategory->id,
                            'quantity' => $definition['quantity'],
                            'is_detailed_quantity' => $definition['detailed'] ?? false,
                        ]
                    );
                    if (!$article->wasRecentlyCreated) {
                        $this->attachImage($article, $definition['image'] ?? null);
                        continue;
                    }
                    $createdArticles++;
                    $this->attachImage($article, $definition['image'] ?? null);

                    if ($manufacturerProperty !== null) {
                        $article->properties()->syncWithoutDetaching([
                            $manufacturerProperty->id => ['value' => $definition['manufacturer']],
                        ]);
                    }
                    if ($roomProperty !== null && $storageRoomId !== null) {
                        $article->properties()->syncWithoutDetaching([
                            $roomProperty->id => ['value' => (string) $storageRoomId],
                        ]);
                    }

                    if ($readyStatus !== null) {
                        $article->statusValues()->syncWithoutDetaching([
                            $readyStatus->id => ['value' => (string) $definition['quantity']],
                        ]);
                    }

                    if ($definition['detailed'] ?? false) {
                        $rng = $random->fork($definition['name']);
                        $brokenIndex = $brokenStatus !== null && $rng->chance(0.6)
                            ? $rng->int(0, $definition['quantity'] - 1)
                            : -1;
                        $inventoryBase = $rng->int(10000, 99999);
                        for ($i = 0; $i < $definition['quantity']; $i++) {
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
                }
            }
        }

        $this->command?->info(sprintf('Inventar: %d Artikel neu angelegt.', $createdArticles));
    }

    /**
     * Hängt ein kuratiertes Fundus-Foto an den Artikel (einmalig): Datei auf
     * die public-Disk kopieren, Bild-Row anlegen, WebP-Thumbnail erzeugen.
     */
    private function attachImage(InventoryArticle $article, ?string $imageFile): void
    {
        $imageFile ??= DemoExtrasPools::ARTICLE_IMAGES[$article->name] ?? null;
        if ($imageFile === null || $article->images()->exists()) {
            return;
        }

        $sourcePath = __DIR__ . '/assets/fundus/' . $imageFile;
        if (!is_file($sourcePath)) {
            return;
        }

        $targetPath = 'inventory_articles/demo_' . $imageFile;
        Storage::disk('public')->put($targetPath, (string) file_get_contents($sourcePath));

        $thumbnail = app(InventoryArticleImageService::class)->generateThumbnail($targetPath);
        $article->images()->create([
            'image' => $targetPath,
            'thumbnail' => $thumbnail,
            'is_main_image' => true,
            'order' => 1,
        ]);
    }
}
