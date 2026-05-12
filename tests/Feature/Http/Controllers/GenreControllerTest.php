<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class GenreControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_genre(): void
    {
        $this->post(route('genres.store'), ['name' => 'Pop', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_genre(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('genres.store'), [
            'name' => 'Rock',
            'color' => '#ff0000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('genres', ['name' => 'Rock']);
    }

    #[Test]
    public function admin_can_update_genre(): void
    {
        $this->actingAsAdmin();
        $genre = Genre::query()->forceCreate([
            'name' => 'Classical',
            'color' => '#aaa',
        ]);

        $response = $this->patch(route('genres.update', $genre), [
            'name' => 'Classical Updated',
            'color' => '#bbb',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Classical Updated',
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_store_genre(): void
    {
        // GenrePolicy::create requires PROJECT_SETTINGS_UPDATE permission.
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('genres.store'), [
            'name' => 'Jazz',
            'color' => '#fff',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function user_with_project_settings_update_permission_can_store_genre(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $response = $this->post(route('genres.store'), [
            'name' => 'Metal',
            'color' => '#000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('genres', ['name' => 'Metal']);
    }
}
