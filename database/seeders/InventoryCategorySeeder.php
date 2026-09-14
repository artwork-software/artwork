<?php

namespace Database\Seeders;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws RandomException
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Vorhandene Daten löschen
        InventoryCategory::truncate();
        InventorySubCategory::truncate();
        InventoryArticle::truncate();
        InventoryArticleProperties::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1️⃣ Erstelle 10 Kategorien
        $categories = InventoryCategory::factory(30)->create();

        // 2️⃣ Erstelle 15 Properties
        $properties = InventoryArticleProperties::factory(15)->create();

        // 3️⃣ Weisen wir die Properties zufällig Kategorien & Subkategorien zu
        foreach ($categories as $category) {
            // Zufällig 2-5 Subkategorien erstellen
            $subCategories = InventorySubCategory::factory(random_int(2, 5))->create([
                'inventory_category_id' => $category->id,
            ]);

            // Zufällig 3-7 Properties der Kategorie zuweisen
            $randomProperties = $properties->random(random_int(3, 7));
            foreach ($randomProperties as $property) {
                $category->properties()->syncWithoutDetaching([
                    $property->id => ['value' => fake()->word()]
                ]);
            }

            // Dasselbe für jede Subkategorie
            foreach ($subCategories as $subCategory) {
                $randomProperties = $properties->random(random_int(2, 5));
                foreach ($randomProperties as $property) {
                    $subCategory->properties()->syncWithoutDetaching([
                        $property->id => ['value' => fake()->word()]
                    ]);
                }
            }
        }

        // 4️⃣ Erstelle zufällige Artikel und verknüpfe sie mit den richtigen Properties
        for ($i = 0; $i < 500; $i++) {
            $category = $categories->random();
            $subCategory = $category->subCategories->isNotEmpty()
                ? $category->subCategories->random()
                : InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);

            $article = InventoryArticle::factory()->create([
                'inventory_category_id' => $category->id,
                'inventory_sub_category_id' => $subCategory->id,
            ]);

            // Debugging: Prüfen, ob Properties gespeichert wurden
            $category->load('properties');
            dump("📌 Kategorie: {$category->name}, Properties: ", $category->properties);

            foreach ($subCategories as $subCategory) {
                $subCategory->load('properties');
                dump("📌 Sub-Kategorie: {$subCategory->name}, Properties: ", $subCategory->properties);
            }

            // 1️⃣ Lade die Properties, damit sie verfügbar sind
            $category->load(['properties' => function ($query): void {
                $query->withPivot('value');
            }]);
            $subCategory->load(['properties' => function ($query): void {
                $query->withPivot('value');
            }]);

            $categoryProperties = $category->properties ?? collect();
            $subCategoryProperties = $subCategory->properties ?? collect();
            $articleProperties = $categoryProperties->merge($subCategoryProperties)->unique();

            // 2️⃣ Debugging: Ausgabe der gefundenen Properties
            if ($articleProperties->isEmpty()) {
                dump("⚠ Keine Properties für Artikel #{$article->id} (Kategorie: {$category->name}, Sub-Kategorie: {$subCategory->name})");
            }

            // 3️⃣ Weisen dem Artikel die Properties MIT WERTEN zu
            foreach ($articleProperties as $property) {
                $value = fake()->word();
                dump("🔹 Artikel #{$article->id}: {$property->name} → {$value}");
                $article->properties()->attach($property->id, ['value' => $value]);
            }
        }
    }
}
