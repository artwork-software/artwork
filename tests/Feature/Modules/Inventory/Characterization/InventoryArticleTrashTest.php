<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung des Inventar-Papierkorbs: Liste, Wiederherstellen, endgültig Löschen (einzeln
 * und alle) sowie die Tag-Sperre beim Löschen. Alle Endpunkte hängen an "inventory.delete".
 */
final class InventoryArticleTrashTest extends FeatureTestCase
{
    private function actingAsDeleter(): User
    {
        return $this->actingAsUserWith(PermissionEnum::INVENTORY_DELETE->value);
    }

    private function trashedArticle(array $attributes = []): InventoryArticle
    {
        $article = InventoryArticle::factory()->create($attributes);
        $article->delete();

        return $article;
    }

    private function restrictedTagFor(InventoryArticle $article): InventoryTag
    {
        $tag = InventoryTag::factory()->create(['color' => '#000000', 'has_restricted_permissions' => true]);
        $article->tags()->attach($tag->id);

        return $tag;
    }

    #[Test]
    public function the_trash_lists_only_trashed_articles_and_can_be_searched(): void
    {
        $this->actingAsDeleter();
        $this->trashedArticle(['name' => 'Zz-Papierkorb-Scheinwerfer']);
        $this->trashedArticle(['name' => 'Zz-Papierkorb-Kabel']);
        InventoryArticle::factory()->create(['name' => 'Zz-Papierkorb-Aktiv']);

        $this->get(route('inventory.articles.trash', ['search' => 'Zz-Papierkorb-Schein']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Trash/InventoryArticles')
                ->has('trashedArticles.data', 1)
                ->where('trashedArticles.data.0.name', 'Zz-Papierkorb-Scheinwerfer'));
    }

    #[Test]
    public function a_trashed_article_is_restored_with_its_detailed_articles(): void
    {
        $this->actingAsDeleter();
        $article = InventoryArticle::factory()->create(['is_detailed_quantity' => true]);
        $detail = InventoryDetailedQuantityArticle::factory()->create(['inventory_article_id' => $article->id]);
        $this->delete(route('articles.destroy', $article))->assertOk();
        $this->assertSoftDeleted($detail);

        $this->patch(route('articles.restore', $article->id))->assertOk();

        $this->assertNotSoftDeleted($article);
        $this->assertNotSoftDeleted($detail);
    }

    #[Test]
    public function restoring_an_active_article_is_not_found(): void
    {
        $this->actingAsDeleter();
        $article = InventoryArticle::factory()->create();

        $this->patch(route('articles.restore', $article->id))->assertNotFound();
    }

    #[Test]
    public function a_trashed_article_is_deleted_permanently(): void
    {
        $this->actingAsDeleter();
        $article = InventoryArticle::factory()->create(['is_detailed_quantity' => true]);
        $detail = InventoryDetailedQuantityArticle::factory()->create(['inventory_article_id' => $article->id]);
        $article->delete();

        $this->delete(route('articles.forceDelete', $article->id))->assertOk();

        $this->assertDatabaseMissing('inventory_articles', ['id' => $article->id]);
        $this->assertDatabaseMissing('inventory_detailed_quantity_articles', ['id' => $detail->id]);
    }

    #[Test]
    public function emptying_the_trash_keeps_active_articles(): void
    {
        $this->actingAsDeleter();
        $first = $this->trashedArticle();
        $second = $this->trashedArticle();
        $active = InventoryArticle::factory()->create();

        $this->delete(route('articles.forceDeleteAll'))->assertOk();

        $this->assertSame(0, InventoryArticle::onlyTrashed()->count());
        $this->assertDatabaseMissing('inventory_articles', ['id' => $first->id]);
        $this->assertDatabaseMissing('inventory_articles', ['id' => $second->id]);
        $this->assertNotSoftDeleted($active);
    }

    #[Test]
    public function moving_an_article_with_a_foreign_restricted_tag_to_the_trash_is_forbidden(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::INVENTORY_DELETE->value,
        ]);
        $article = InventoryArticle::factory()->create();
        $this->restrictedTagFor($article);

        $this->delete(route('articles.destroy', $article))->assertForbidden();

        $this->assertNotSoftDeleted($article);
    }

    #[Test]
    public function a_user_released_on_the_restricted_tag_may_move_the_article_to_the_trash(): void
    {
        $user = $this->actingAsDeleter();
        $article = InventoryArticle::factory()->create();
        $this->restrictedTagFor($article)->allowedUsers()->attach($user->id);

        $this->delete(route('articles.destroy', $article))->assertOk();

        $this->assertSoftDeleted($article);
    }

    #[Test]
    public function force_delete_respects_restricted_tags(): void
    {
        $this->actingAsDeleter();
        $article = $this->trashedArticle();
        $this->restrictedTagFor($article);

        $this->delete(route('articles.forceDelete', $article->id))->assertForbidden();
        $this->patch(route('articles.restore', $article->id))->assertForbidden();
    }

    #[Test]
    public function emptying_the_trash_keeps_articles_with_restricted_tags(): void
    {
        $this->actingAsDeleter();
        $restricted = $this->trashedArticle();
        $this->restrictedTagFor($restricted);
        $free = $this->trashedArticle();

        $this->delete(route('articles.forceDeleteAll'))->assertSuccessful();

        $this->assertSoftDeleted($restricted);
        $this->assertDatabaseMissing('inventory_articles', ['id' => $free->id]);
    }

    #[Test]
    public function force_delete_only_accepts_trashed_articles(): void
    {
        $this->actingAsDeleter();
        $article = InventoryArticle::factory()->create();

        $this->delete(route('articles.forceDelete', $article->id))->assertNotFound();
        $this->assertNotSoftDeleted($article);
    }

    #[Test]
    public function users_without_the_delete_permission_are_forbidden(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $trashed = $this->trashedArticle();
        $active = InventoryArticle::factory()->create();

        $this->get(route('inventory.articles.trash'))->assertForbidden();
        $this->delete(route('articles.destroy', $active))->assertForbidden();
        $this->patch(route('articles.restore', $trashed->id))->assertForbidden();
        $this->delete(route('articles.forceDelete', $trashed->id))->assertForbidden();
        $this->delete(route('articles.forceDeleteAll'))->assertForbidden();

        $this->assertNotSoftDeleted($active);
        $this->assertSoftDeleted($trashed);
    }
}
