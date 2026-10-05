<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die $appends `room`/`manufacturer` der Artikel lösten je unterschiedlichem Wert eine
 * Query aus (Inventarübersicht: 55 crm_contacts-Queries). InventoryArticleCollection lädt
 * die Lookups vor dem Serialisieren gebündelt; preloadPropertyLookupsDeep() deckt
 * verschachtelte Strukturen (Kategorien → Artikel) ab.
 */
final class InventoryArticleLookupPreloadTest extends FeatureTestCase
{
    private const ROWS = 6;

    private InventoryArticleProperties $manufacturerProperty;
    private InventoryArticleProperties $roomProperty;
    /** @var array<int, CrmContact> */
    private array $manufacturers = [];
    /** @var array<int, Room> */
    private array $rooms = [];

    protected function setUp(): void
    {
        parent::setUp();
        InventoryArticle::flushPropertyLookupCaches();

        $this->manufacturerProperty = InventoryArticleProperties::factory()->create(['type' => 'manufacturer']);
        $this->roomProperty = InventoryArticleProperties::factory()->create(['type' => 'room']);

        $type = CrmContactType::create([
            'name' => 'Hersteller',
            'slug' => 'hersteller-test',
            'is_system' => false,
            'is_active' => true,
        ]);
        for ($i = 0; $i < self::ROWS; $i++) {
            $this->manufacturers[] = CrmContact::create([
                'crm_contact_type_id' => $type->id,
                'display_name' => 'Hersteller ' . $i,
                'is_active' => true,
            ]);
            $this->rooms[] = Room::factory()->create(['name' => 'Raum ' . $i]);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, InventoryArticle>
     */
    private function articlesWithLookups(?InventoryCategory $category = null): \Illuminate\Database\Eloquent\Collection
    {
        $ids = [];
        for ($i = 0; $i < self::ROWS; $i++) {
            $article = InventoryArticle::factory()->create(
                $category ? ['inventory_category_id' => $category->id] : []
            );
            $article->properties()->attach($this->manufacturerProperty->id, ['value' => $this->manufacturers[$i]->id]);
            $article->properties()->attach($this->roomProperty->id, ['value' => $this->rooms[$i]->id]);
            $ids[] = $article->id;
        }

        return InventoryArticle::query()->with('properties')->whereIn('id', $ids)->orderBy('id')->get();
    }

    /**
     * @return array{crm: int, rooms: int}
     */
    private function countLookupQueries(callable $action): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $action();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'crm' => count(array_filter($log, fn (array $q) => str_contains($q['query'], 'crm_contacts'))),
            'rooms' => count(array_filter($log, fn (array $q) => str_contains($q['query'], 'from `rooms`'))),
        ];
    }

    #[Test]
    public function serializing_a_collection_resolves_all_manufacturers_and_rooms_with_one_query_each(): void
    {
        $articles = $this->articlesWithLookups();

        $payload = null;
        $queries = $this->countLookupQueries(function () use ($articles, &$payload): void {
            $payload = $articles->toArray();
        });

        $this->assertSame(['crm' => 1, 'rooms' => 1], $queries);
        $this->assertCount(self::ROWS, $payload);
        foreach ($payload as $i => $article) {
            $this->assertSame('Hersteller ' . $i, $article['manufacturer']['name']);
            $this->assertSame($this->manufacturers[$i]->id, $article['manufacturer']['id']);
            $this->assertSame($this->manufacturerProperty->id, $article['manufacturer']['property_id']);
            $this->assertSame('Raum ' . $i, $article['room']['name']);
            $this->assertSame($this->rooms[$i]->id, $article['room']['id']);
        }
    }

    #[Test]
    public function deep_preload_covers_articles_nested_in_categories(): void
    {
        $category = InventoryCategory::factory()->create();
        $this->articlesWithLookups($category);
        $categories = InventoryCategory::query()
            ->with('articles.properties')
            ->whereKey($category->id)
            ->get();

        $queries = $this->countLookupQueries(
            static fn () => InventoryArticle::preloadPropertyLookupsDeep($categories)
        );
        $this->assertSame(['crm' => 1, 'rooms' => 1], $queries, 'Deep-Preload: je eine Query');

        $payload = null;
        $queries = $this->countLookupQueries(function () use ($categories, &$payload): void {
            $payload = $categories->toArray();
        });
        $this->assertSame(['crm' => 0, 'rooms' => 0], $queries, 'Serialisierung nach Preload ohne Queries');
        $this->assertSame('Hersteller 0', $payload[0]['articles'][0]['manufacturer']['name']);
        $this->assertSame('Raum 0', $payload[0]['articles'][0]['room']['name']);
    }

    /**
     * Detail-Artikel (detailedArticleQuantities) haben dieselben $appends; ihre Lookups laufen
     * über denselben Cache und werden mit den Artikeln zusammen vorgeladen — vorher eine
     * properties-Query je Detail-Artikel (/inventory/articles: 42 bei 15 Artikeln).
     */
    #[Test]
    public function detailed_quantities_share_the_preloaded_lookups(): void
    {
        $article = InventoryArticle::factory()->create();
        for ($i = 0; $i < self::ROWS; $i++) {
            $detail = InventoryDetailedQuantityArticle::query()->create([
                'inventory_article_id' => $article->id,
                'name' => 'Detail ' . $i,
                'quantity' => 1,
                'external_id' => 'ext-' . $i,
                'inventory_number' => 'inv-' . $i,
            ]);
            $detail->properties()->attach($this->manufacturerProperty->id, ['value' => $this->manufacturers[$i]->id]);
            $detail->properties()->attach($this->roomProperty->id, ['value' => $this->rooms[$i]->id]);
        }

        $articles = InventoryArticle::query()
            ->with(['properties', 'detailedArticleQuantities.properties'])
            ->whereKey($article->id)
            ->get();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $payload = $articles->toArray();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $propertyQueries = count(array_filter(
            $log,
            fn (array $q) => str_contains($q['query'], 'inventory_article_properties')
        ));
        $this->assertSame(0, $propertyQueries, 'properties der Detail-Artikel sind vorgeladen');
        $this->assertSame(1, count(array_filter($log, fn (array $q) => str_contains($q['query'], 'crm_contacts'))));
        $this->assertSame(1, count(array_filter($log, fn (array $q) => str_contains($q['query'], 'from `rooms`'))));

        $details = $payload[0]['detailed_article_quantities'];
        $this->assertCount(self::ROWS, $details);
        foreach ($details as $i => $detail) {
            $this->assertSame('Hersteller ' . $i, $detail['manufacturer']['name']);
            $this->assertSame('Raum ' . $i, $detail['room']['name']);
        }
    }

    /** Cache je Prozess: nach Umbenennung eines Herstellers/Raums muss der neue Name erscheinen */
    #[Test]
    public function renaming_a_manufacturer_or_room_invalidates_the_lookup_cache(): void
    {
        $articles = $this->articlesWithLookups();
        $this->assertSame('Hersteller 0', $articles->toArray()[0]['manufacturer']['name']);
        $this->assertSame('Raum 0', $articles->toArray()[0]['room']['name']);

        $this->manufacturers[0]->update(['display_name' => 'Umbenannt GmbH']);
        $this->rooms[0]->update(['name' => 'Neuer Raum']);

        $this->assertSame('Umbenannt GmbH', $articles->toArray()[0]['manufacturer']['name']);
        $this->assertSame('Neuer Raum', $articles->toArray()[0]['room']['name']);
    }

    #[Test]
    public function unknown_lookup_values_resolve_to_null_without_repeated_queries(): void
    {
        $article = InventoryArticle::factory()->create();
        $article->properties()->attach($this->manufacturerProperty->id, ['value' => 999999]);
        $articles = InventoryArticle::query()->with('properties')->whereKey($article->id)->get();

        $first = $this->countLookupQueries(fn () => $articles->toArray());
        $second = $this->countLookupQueries(fn () => $articles->toArray());

        $this->assertNull($articles->first()->toArray()['manufacturer']);
        $this->assertSame(1, $first['crm']);
        $this->assertSame(0, $second['crm'], 'nicht auflösbarer Wert wird nicht erneut abgefragt');
    }
}
