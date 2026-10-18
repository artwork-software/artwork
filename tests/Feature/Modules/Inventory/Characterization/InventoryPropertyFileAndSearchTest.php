<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Datei-Eigenschaften an Inventar-Artikeln (Upload, Download, Löschen nur
 * unterhalb von uploads/inventory-properties), der Artikelsuche über die API und der
 * Unterkategorie-Ansicht im Inventar.
 */
final class InventoryPropertyFileAndSearchTest extends FeatureTestCase
{
    private string $diskRoot;

    /**
     * Eigenes Wurzelverzeichnis statt des gemeinsamen Storage::fake-Ordners, damit parallel
     * laufende Testläufe die Dateien nicht zwischendurch wegräumen.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = storage_path('framework/testing/disks/inventory-property-files-' . uniqid());
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => $this->diskRoot]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    #[Test]
    public function a_property_file_is_stored_under_a_uuid_folder_with_a_sanitized_name(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);

        $response = $this->postJson(route('inventory-management.articles.property-file.upload'), [
            'file' => UploadedFile::fake()->image('Prüf protokoll#1.JPG'),
        ])->assertOk();

        $path = $response->json('path');
        $this->assertMatchesRegularExpression(
            '#^uploads/inventory-properties/[0-9a-f-]{36}/Prüf protokoll_1\.jpg$#u',
            $path
        );
        $this->assertSame('Prüf protokoll_1.jpg', $response->json('name'));
        Storage::disk('local')->assertExists($path);
    }

    #[Test]
    public function uploading_a_property_file_needs_the_edit_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('inventory-management.articles.property-file.upload'), [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertForbidden();
    }

    #[Test]
    public function uploading_rejects_disallowed_file_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);

        $this->postJson(route('inventory-management.articles.property-file.upload'), [
            'file' => UploadedFile::fake()->create('skript.html', 1, 'text/html'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    #[Test]
    public function a_property_file_can_be_downloaded_by_any_user(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::disk('local')->put('uploads/inventory-properties/abc/handbuch.pdf', 'inhalt');

        $this->get(route('inventory-management.articles.property-file.download', [
            'path' => 'uploads/inventory-properties/abc/handbuch.pdf',
        ]))->assertOk()->assertDownload('handbuch.pdf');
    }

    #[Test]
    public function a_missing_property_file_is_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('inventory-management.articles.property-file.download', [
            'path' => 'uploads/inventory-properties/abc/fehlt.pdf',
        ]))->assertNotFound();
    }

    #[Test]
    public function paths_outside_the_property_folder_are_forbidden(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        Storage::disk('local')->put('geheim.txt', 'x');

        $this->get(route('inventory-management.articles.property-file.download', ['path' => 'geheim.txt']))
            ->assertForbidden();
        $this->get(route('inventory-management.articles.property-file.download', [
            'path' => 'uploads/inventory-properties/../../geheim.txt',
        ]))->assertForbidden();
        $this->deleteJson(route('inventory-management.articles.property-file.delete'), ['path' => 'geheim.txt'])
            ->assertForbidden();

        Storage::disk('local')->assertExists('geheim.txt');
    }

    #[Test]
    public function deleting_a_property_file_removes_it_with_its_folder(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        Storage::disk('local')->put('uploads/inventory-properties/abc/handbuch.pdf', 'inhalt');

        $this->deleteJson(route('inventory-management.articles.property-file.delete'), [
            'path' => 'uploads/inventory-properties/abc/handbuch.pdf',
        ])->assertOk()->assertJson(['deleted' => true]);

        Storage::disk('local')->assertMissing('uploads/inventory-properties/abc/handbuch.pdf');
        Storage::disk('local')->assertDirectoryEmpty('uploads/inventory-properties');
    }

    #[Test]
    public function deleting_a_property_file_needs_the_edit_permission(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::disk('local')->put('uploads/inventory-properties/abc/handbuch.pdf', 'inhalt');

        $this->deleteJson(route('inventory-management.articles.property-file.delete'), [
            'path' => 'uploads/inventory-properties/abc/handbuch.pdf',
        ])->assertForbidden();

        Storage::disk('local')->assertExists('uploads/inventory-properties/abc/handbuch.pdf');
    }

    #[Test]
    public function the_article_search_returns_an_empty_list_for_a_blank_term(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('inventory.articles.search'), ['article_search' => '   '])
            ->assertOk()
            ->assertExactJson([]);
    }

    #[Test]
    public function the_article_search_needs_a_login(): void
    {
        $this->postJson(route('inventory.articles.search'), ['article_search' => 'Kabel'])->assertUnauthorized();
    }

    #[Test]
    public function the_sub_category_view_renders_the_inventory_page_for_that_sub_category(): void
    {
        $this->actingAs(User::factory()->create());
        $category = InventoryCategory::factory()->create();
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);
        InventoryArticle::factory()->create(['inventory_category_id' => $category->id]);

        $this->get(route('inventory.sub.category.show', [
            'inventoryCategory' => $category->id,
            'inventorySubCategory' => $subCategory->id,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Index')
                ->where('currentCategory.id', $category->id)
                ->where('currentSubCategory.id', $subCategory->id)
                ->where('articles.total', 1)
                ->where('articles.data.0.id', $article->id));
    }
}
