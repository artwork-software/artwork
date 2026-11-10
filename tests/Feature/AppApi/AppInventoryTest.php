<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Inventory in the app: lookup on site — list, search, article, availability — with "View inventory". */
final class AppInventoryTest extends TestCase
{
    private InventoryArticleStatus $ready;
    private InventoryArticleStatus $broken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsApiUserWith(PermissionEnum::INVENTORY_VIEW->value);
        $this->ready = $this->createStatus('Einsatzbereit', 1, default: true);
        $this->broken = $this->createStatus('Defekt', 2);
    }

    #[Test]
    public function meSaysWhetherTheInventoryIsOn(): void
    {
        $this->getJson(route('app.v1.me'))->assertJsonPath('user.permissions.can_view_inventory', true);

        $settings = app(ModuleSettings::class);
        $settings->inventory = false;
        $settings->save();

        $this->getJson(route('app.v1.me'))->assertJsonPath('user.permissions.can_view_inventory', false);
        $this->getJson(route('app.v1.inventory.articles'))->assertForbidden();
    }

    #[Test]
    public function withoutTheViewPermissionTheInventoryIsClosed(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.me'))->assertJsonPath('user.permissions.can_view_inventory', false);
        $this->getJson(route('app.v1.inventory.categories'))->assertForbidden();
        $this->getJson(route('app.v1.inventory.articles'))->assertForbidden();
    }

    #[Test]
    public function inventoryDispositionAlsoOpensTheInventory(): void
    {
        $this->actingAsApiUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);

        $this->getJson(route('app.v1.me'))->assertJsonPath('user.permissions.can_view_inventory', true);
        $this->getJson(route('app.v1.inventory.articles'))->assertOk();
    }

    #[Test]
    public function theListPagesThroughTheArticlesInTheWebsOrder(): void
    {
        $light = InventoryCategory::factory()->create(['name' => 'Licht']);
        $sound = InventoryCategory::factory()->create(['name' => 'Ton']);
        $spots = InventorySubCategory::factory()->create(['name' => 'Spots', 'inventory_category_id' => $light->id]);
        $this->article('Mikrofon', $sound);
        $this->article('Fresnel', $light, $spots);

        $this->getJson(route('app.v1.inventory.articles'))
            ->assertOk()
            ->assertJsonPath('articles.0.name', 'Fresnel')
            ->assertJsonPath('articles.0.category', ['id' => $light->id, 'name' => 'Licht'])
            ->assertJsonPath('articles.0.subcategory', ['id' => $spots->id, 'name' => 'Spots'])
            ->assertJsonPath('articles.1.name', 'Mikrofon')
            ->assertJsonPath('meta', ['current_page' => 1, 'last_page' => 1, 'total' => 2]);

        $this->getJson(route('app.v1.inventory.articles', ['category_id' => $sound->id]))
            ->assertJsonCount(1, 'articles')
            ->assertJsonPath('articles.0.name', 'Mikrofon');
    }

    #[Test]
    public function theSearchFindsNamesAndInventoryNumbers(): void
    {
        config()->set('scout.driver', 'collection');
        $fresnel = $this->article('Fresnel');
        $this->article('Mikrofon');

        $this->getJson(route('app.v1.inventory.articles', ['q' => 'fresn']))
            ->assertJsonCount(1, 'articles')
            ->assertJsonPath('articles.0.id', $fresnel->id);

        $this->getJson(route('app.v1.inventory.articles', ['q' => $fresnel->inventory_number]))
            ->assertJsonCount(1, 'articles')
            ->assertJsonPath('articles.0.id', $fresnel->id);
    }

    #[Test]
    public function anArticleShowsItsStockPlaceAndProperties(): void
    {
        $article = $this->article('Fresnel', quantity: 10);
        $article->statusValues()->attach([
            $this->ready->id => ['value' => 7],
            $this->broken->id => ['value' => 3],
        ]);
        $room = Room::factory()->create(['name' => 'Lager Nord']);
        $article->properties()->attach([
            $this->property('Lagerort', 'room')->id => ['value' => (string) $room->id],
            $this->property('Leistung', 'text')->id => ['value' => '1000 W'],
            $this->property('Geprüft', 'checkbox')->id => ['value' => 'true'],
            $this->property('Handbuch', 'file')->id => ['value' => 'inventory/handbuch.pdf'],
            $this->property('Notiz', 'text')->id => ['value' => ''],
        ]);
        InventoryArticleImage::factory()->create([
            'inventory_article_id' => $article->id,
            'image' => 'inventory_articles/fresnel.jpg',
            'thumbnail' => 'inventory_articles/thumbnails/fresnel.webp',
            'is_main_image' => true,
        ]);

        $response = $this->getJson(route('app.v1.inventory.articles.show', $article))->assertOk();

        $response
            ->assertJsonPath('article.room', ['id' => $room->id, 'name' => 'Lager Nord'])
            ->assertJsonPath('article.ready_quantity', 7)
            ->assertJsonPath('article.total_quantity', 10)
            ->assertJsonPath('article.statuses.0.name', 'Einsatzbereit')
            ->assertJsonPath('article.statuses.0.ready', true)
            ->assertJsonPath('article.statuses.1.quantity', 3)
            ->assertJsonPath('article.statuses.1.ready', false)
            ->assertJsonPath('article.units', []);
        $this->assertSame(
            [['Leistung', '1000 W'], ['Geprüft', '1']],
            array_map(
                static fn (array $row): array => [$row['name'], $row['value']],
                $response->json('article.properties'),
            ),
        );
        $this->assertStringEndsWith('/storage/inventory_articles/fresnel.jpg', $response->json('article.images.0.url'));
        $this->assertStringEndsWith(
            '/storage/inventory_articles/thumbnails/fresnel.webp',
            $response->json('article.thumbnail_url'),
        );
    }

    #[Test]
    public function aDetailedArticleListsItsUnitsAndSumsThemPerStatus(): void
    {
        $article = $this->article('Funkstrecke', quantity: 3, detailed: true);
        foreach ([3 => $this->ready, 1 => $this->broken, 2 => $this->ready] as $number => $status) {
            InventoryDetailedQuantityArticle::factory()->create([
                'inventory_article_id' => $article->id,
                'inventory_article_status_id' => $status->id,
                'detail_number' => $number,
                'inventory_number' => sprintf('%s-%03d', $article->inventory_number, $number),
                'quantity' => 1,
            ]);
        }

        $this->getJson(route('app.v1.inventory.articles.show', $article))
            ->assertOk()
            ->assertJsonPath('article.ready_quantity', 2)
            ->assertJsonPath('article.statuses', [
                [
                    'id' => $this->ready->id, 'name' => 'Einsatzbereit', 'color' => '#00aa00',
                    'quantity' => 2, 'ready' => true,
                ],
                [
                    'id' => $this->broken->id, 'name' => 'Defekt', 'color' => '#00aa00',
                    'quantity' => 1, 'ready' => false,
                ],
            ])
            ->assertJsonCount(3, 'article.units')
            ->assertJsonPath('article.units.0.status.name', 'Defekt');
    }

    #[Test]
    public function availabilityTakesOffWhatIssuesBookInTheRange(): void
    {
        $article = $this->article('Fresnel', quantity: 5);
        $issue = InternalIssue::factory()->create([
            'start_date' => '2026-11-10',
            'start_time' => '14:00',
            'end_date' => '2026-11-12',
            'end_time' => '18:00',
        ]);
        $issue->articles()->attach($article->id, ['quantity' => 2]);
        $range = static fn (string $start, string $end): array => [
            'article' => $article, 'start' => $start, 'end' => $end,
        ];

        $this->getJson(route('app.v1.inventory.articles.availability', $range('2026-11-11', '2026-11-11')))
            ->assertOk()
            ->assertJsonPath('available', 3)
            ->assertJsonPath('reserved', 2)
            ->assertJsonPath('total', 5);

        $this->getJson(route('app.v1.inventory.articles.availability', $range('2026-11-13', '2026-11-14')))
            ->assertJsonPath('available', 5);
    }

    private function article(
        string $name,
        ?InventoryCategory $category = null,
        ?InventorySubCategory $subCategory = null,
        int $quantity = 1,
        bool $detailed = false,
    ): InventoryArticle {
        $category ??= InventoryCategory::factory()->create(['name' => 'Allgemein']);

        return InventoryArticle::factory()->create([
            'name' => $name,
            'quantity' => $quantity,
            'is_detailed_quantity' => $detailed,
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory?->id,
        ]);
    }

    private function createStatus(string $name, int $order, bool $default = false): InventoryArticleStatus
    {
        $status = new InventoryArticleStatus(['name' => $name, 'color' => '#00aa00', 'order' => $order]);
        $status->default = $default;
        $status->save();

        return $status;
    }

    private function property(string $name, string $type): InventoryArticleProperties
    {
        return InventoryArticleProperties::factory()->create(['name' => $name, 'type' => $type]);
    }
}
