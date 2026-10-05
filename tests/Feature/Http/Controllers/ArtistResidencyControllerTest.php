<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ArtistResidencyControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $project = Project::factory()->create();
        $this->post('/project/artist-residencies/' . $project->id . '/artist-residencies', [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $ar = ArtistResidency::factory()->create();
        $this->delete(route('artist-residency.destroy', $ar))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_artist_residency(): void
    {
        $this->actingAsAdmin();
        $ar = ArtistResidency::factory()->create();

        $response = $this->delete(route('artist-residency.destroy', $ar));

        $response->assertOk();
        $this->assertDatabaseMissing('artist_residencies', ['id' => $ar->id]);
    }

    #[Test]
    public function admin_can_duplicate_artist_residency(): void
    {
        $this->actingAsAdmin();
        $ar = ArtistResidency::factory()->create();
        $before = ArtistResidency::query()->count();

        $response = $this->post(route('artist_residencies.duplicate', $ar));

        $response->assertOk();
        $this->assertGreaterThan($before, ArtistResidency::query()->count());
    }

    #[Test]
    public function update_name_renames_linked_artist_residency_inline(): void
    {
        $this->actingAsAdmin();
        $ar = ArtistResidency::factory()->create(['do_not_save_artist' => false, 'name' => 'Old Name']);

        $response = $this->patchJson(route('artist-residencies.update-name', $ar), [
            'field' => 'name',
            'value' => '  New Name  ',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('artist_residencies', ['id' => $ar->id, 'name' => 'New Name']);
    }

    #[Test]
    public function update_name_can_clear_first_name_but_not_name(): void
    {
        $this->actingAsAdmin();
        $ar = ArtistResidency::factory()->create(['name' => 'Keep Me', 'first_name' => 'Ada']);

        $this->patchJson(route('artist-residencies.update-name', $ar), ['field' => 'first_name', 'value' => ''])
            ->assertOk();
        $this->assertDatabaseHas('artist_residencies', ['id' => $ar->id, 'first_name' => null]);

        $this->patchJson(route('artist-residencies.update-name', $ar), ['field' => 'name', 'value' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
        $this->assertDatabaseHas('artist_residencies', ['id' => $ar->id, 'name' => 'Keep Me']);
    }

    #[Test]
    public function update_name_rejects_unknown_field(): void
    {
        $this->actingAsAdmin();
        $ar = ArtistResidency::factory()->create();

        $this->patchJson(route('artist-residencies.update-name', $ar), ['field' => 'phone_number', 'value' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['field']);
    }
}
