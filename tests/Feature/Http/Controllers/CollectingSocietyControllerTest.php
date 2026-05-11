<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CollectingSocietyControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_index(): void
    {
        $this->get(route('collecting_societies.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_index(): void
    {
        $this->actingAsAdmin();
        CollectingSociety::query()->forceCreate(['name' => 'GEMA', 'color' => '#fff']);

        $response = $this->get(route('collecting_societies.index'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('collecting_societies.store'), ['name' => 'X', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('collecting_societies.store'), [
            'name' => 'GEMA',
            'color' => '#abcdef',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('collecting_societies', ['name' => 'GEMA']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $cs = CollectingSociety::query()->forceCreate(['name' => 'Old', 'color' => '#111']);

        $response = $this->patch(route('collecting_societies.update', $cs), [
            'name' => 'New',
            'color' => '#222',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collecting_societies', ['id' => $cs->id, 'name' => 'New']);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $cs = CollectingSociety::query()->forceCreate(['name' => 'X', 'color' => '#111']);

        $response = $this->delete(route('collecting_societies.delete', $cs));

        $response->assertRedirect();
        $this->assertSoftDeleted('collecting_societies', ['id' => $cs->id]);
    }
}
