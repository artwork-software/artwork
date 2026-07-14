<?php

/**
 * Tests für das konfigurierbare Größenlimit von Artikelbildern
 * (GeneralSettings::inventory_article_image_max_size_mb).
 */

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class InventoryArticleImageMaxSizeSettingTest extends FeatureTestCase
{
    private InventoryCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAsAdmin();

        $this->category = InventoryCategory::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function storePayload(UploadedFile $image): array
    {
        return [
            'name' => 'Testartikel',
            'description' => '',
            'inventory_category_id' => $this->category->id,
            'quantity' => 1,
            'is_detailed_quantity' => false,
            'main_image_index' => 0,
            'newImages' => [$image],
        ];
    }

    #[Test]
    public function itRejectsImagesLargerThanTheConfiguredDefaultLimit(): void
    {
        // Default limit is 10 MB — an 11 MB image must be rejected.
        $image = UploadedFile::fake()->image('gross.jpg')->size(11 * 1024);

        $this->post(route('inventory-management.articles.store'), $this->storePayload($image))
            ->assertSessionHasErrors('newImages.0');

        $this->assertDatabaseMissing('inventory_articles', ['name' => 'Testartikel']);
    }

    #[Test]
    public function itAcceptsLargerImagesAfterRaisingTheLimit(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->inventory_article_image_max_size_mb = 15;
        $settings->save();

        $image = UploadedFile::fake()->image('gross.jpg')->size(11 * 1024);

        $this->post(route('inventory-management.articles.store'), $this->storePayload($image))
            ->assertSessionHasNoErrors();

        $article = InventoryArticle::query()->where('name', 'Testartikel')->first();
        $this->assertNotNull($article);
        $this->assertCount(1, $article->images);
    }

    #[Test]
    public function itUpdatesTheLimitViaTheSettingsEndpoint(): void
    {
        $this->patch(route('inventory-management.settings.general.update-article-image-max-size'), [
            'inventory_article_image_max_size_mb' => 25,
        ])->assertSessionHasNoErrors();

        $this->assertSame(25, app(GeneralSettings::class)->refresh()->inventory_article_image_max_size_mb);
    }

    #[Test]
    public function itRejectsInvalidLimitValues(): void
    {
        $this->patch(route('inventory-management.settings.general.update-article-image-max-size'), [
            'inventory_article_image_max_size_mb' => 0,
        ])->assertSessionHasErrors('inventory_article_image_max_size_mb');

        $this->patch(route('inventory-management.settings.general.update-article-image-max-size'), [
            'inventory_article_image_max_size_mb' => 'abc',
        ])->assertSessionHasErrors('inventory_article_image_max_size_mb');

        $this->assertSame(10, app(GeneralSettings::class)->refresh()->inventory_article_image_max_size_mb);
    }
}
