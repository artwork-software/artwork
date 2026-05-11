<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\ShiftFilter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftFilterControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_index(): void
    {
        $this->get('/shifts/filters')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/shifts/filters');

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post('/shifts/filters', ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post('/shifts/filters', [
            'name' => 'MyFilter',
            'calendarFilters' => ['roomIds' => [], 'eventTypeIds' => []],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shift_filters', ['name' => 'MyFilter', 'user_id' => $admin->id]);
    }

    #[Test]
    public function admin_can_destroy_filter(): void
    {
        $admin = $this->actingAsAdmin();
        $filter = ShiftFilter::query()->forceCreate([
            'name' => 'X',
            'user_id' => $admin->id,
        ]);

        $response = $this->delete('/shifts/filters/' . $filter->id);

        $response->assertOk();
        $this->assertDatabaseMissing('shift_filters', ['id' => $filter->id]);
    }
}
