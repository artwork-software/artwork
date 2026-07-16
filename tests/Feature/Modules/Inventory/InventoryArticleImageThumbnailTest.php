<?php

/**
 * Tests für die Thumbnail-Erzeugung und HEIC-Konvertierung von Artikelbildern
 * (InventoryArticleImageService + Backfill-Command).
 */

namespace Tests\Feature\Modules\Inventory;

use App\Jobs\GenerateInventoryArticleImageThumbnail;
use Artwork\Modules\Inventory\Console\Commands\GenerateInventoryArticleImageThumbnailsCommand;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Feature\FeatureTestCase;

final class InventoryArticleImageThumbnailTest extends FeatureTestCase
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
            'name' => 'Thumbnailartikel',
            'description' => '',
            'inventory_category_id' => $this->category->id,
            'quantity' => 1,
            'is_detailed_quantity' => false,
            'main_image_index' => 0,
            'newImages' => [$image],
        ];
    }

    private function makeHeicUpload(): UploadedFile
    {
        if (!extension_loaded('imagick') || \Imagick::queryFormats('HEIC') === []) {
            $this->markTestSkipped('Imagick without HEIC support.');
        }

        $imagick = new \Imagick();
        $imagick->newImage(120, 90, new \ImagickPixel('red'));
        $imagick->setImageFormat('heic');

        $path = tempnam(sys_get_temp_dir(), 'heic_test') . '.heic';
        file_put_contents($path, $imagick->getImageBlob());

        return new UploadedFile($path, 'iphone-foto.heic', 'image/heic', null, true);
    }

    #[Test]
    public function itGeneratesAWebpThumbnailOnUpload(): void
    {
        $image = UploadedFile::fake()->image('foto.jpg', 1600, 1200);

        $this->post(route('inventory-management.articles.store'), $this->storePayload($image))
            ->assertSessionHasNoErrors();

        $articleImage = InventoryArticle::query()
            ->where('name', 'Thumbnailartikel')->firstOrFail()
            ->images->first();
        (new GenerateInventoryArticleImageThumbnail($articleImage))
            ->handle(app(InventoryArticleImageService::class));
        $articleImage->refresh();

        $this->assertNotNull($articleImage->thumbnail);
        $this->assertStringStartsWith('inventory_articles/thumbnails/', $articleImage->thumbnail);
        $this->assertStringEndsWith('.webp', $articleImage->thumbnail);
        Storage::disk('public')->assertExists($articleImage->thumbnail);
        Storage::disk('public')->assertExists($articleImage->image);
    }

    #[Test]
    public function itRejectsSvgUploads(): void
    {
        $image = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>'
        );

        $this->post(route('inventory-management.articles.store'), $this->storePayload($image))
            ->assertSessionHasErrors('newImages.0');

        $this->assertDatabaseMissing('inventory_articles', ['name' => 'Thumbnailartikel']);
    }

    #[Test]
    public function forceDeleteRemovesOriginalAndThumbnailFiles(): void
    {
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $this->category->id,
        ]);
        $original = UploadedFile::fake()->image('original.jpg')->store('inventory_articles', 'public');
        $thumbnail = UploadedFile::fake()->image('thumbnail.webp')
            ->store('inventory_articles/thumbnails', 'public');
        $article->images()->create([
            'image' => $original,
            'thumbnail' => $thumbnail,
            'is_main_image' => true,
            'order' => 0,
        ]);

        app(InventoryArticleRepository::class)->forceDelete($article);

        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertMissing($thumbnail);
    }

    #[Test]
    public function itConvertsHeicUploadsToJpeg(): void
    {
        $image = $this->makeHeicUpload();

        $this->post(route('inventory-management.articles.store'), $this->storePayload($image))
            ->assertSessionHasNoErrors();

        $articleImage = InventoryArticle::query()
            ->where('name', 'Thumbnailartikel')->firstOrFail()
            ->images->first();
        (new GenerateInventoryArticleImageThumbnail($articleImage))
            ->handle(app(InventoryArticleImageService::class));
        $articleImage->refresh();

        $this->assertStringEndsWith('.jpg', $articleImage->image);
        $this->assertNotNull($articleImage->thumbnail);
        Storage::disk('public')->assertExists($articleImage->image);
        Storage::disk('public')->assertExists($articleImage->thumbnail);
    }

    #[Test]
    public function backfillCommandGeneratesThumbnailsAndConvertsHeic(): void
    {
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $this->category->id,
        ]);

        // Bestandsbild ohne Thumbnail (Zustand vor dieser Änderung).
        $legacyPath = UploadedFile::fake()->image('bestand.png', 800, 600)
            ->store('inventory_articles', 'public');
        $legacyImage = $article->images()->create([
            'image' => $legacyPath,
            'is_main_image' => true,
            'order' => 0,
        ]);

        // Bestands-HEIC (vor der Format-Whitelist hochgeladen).
        $heicUpload = $this->makeHeicUpload();
        $heicPath = $heicUpload->store('inventory_articles', 'public');
        $heicImage = $article->images()->create([
            'image' => $heicPath,
            'is_main_image' => false,
            'order' => 0,
        ]);

        // Direkt aufrufen statt $this->artisan(): das Booten der kompletten
        // Command-Liste kollidiert mit Mail::fake() aus dem Test-Setup
        // (SendNotificationsEmailSummariesCommand typisiert MailManager).
        $command = new GenerateInventoryArticleImageThumbnailsCommand();
        $command->setLaravel($this->app);
        $this->assertSame(0, $command->run(new ArrayInput([]), new BufferedOutput()));

        $legacyImage->refresh();
        $this->assertNotNull($legacyImage->thumbnail);
        Storage::disk('public')->assertExists($legacyImage->thumbnail);

        $heicImage->refresh();
        $this->assertStringEndsWith('.jpg', $heicImage->image);
        $this->assertNotNull($heicImage->thumbnail);
        Storage::disk('public')->assertExists($heicImage->image);
        Storage::disk('public')->assertExists($heicImage->thumbnail);
        Storage::disk('public')->assertMissing($heicPath);
    }
}
