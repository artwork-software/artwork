<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\DayService\Models\DayService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class DayServiceControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('day-service.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('day-service.index'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('day-service.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('day-service.store'), [
            'name' => 'TimeOff',
            'icon' => 'IconClock',
            'hex_color' => '#abcdef',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('day_services', ['name' => 'TimeOff']);
    }

    #[Test]
    public function validationErrorsUseTranslatedAttributeNames(): void
    {
        $this->actingAsAdmin();

        $this->post(route('day-service.store'))->assertSessionHasErrors([
            'name' => __('validation.required', ['attribute' => __('Name')]),
            'icon' => __('validation.required', ['attribute' => __('Icon')]),
            'hex_color' => __('validation.required', ['attribute' => __('Color')]),
        ]);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $ds = DayService::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('day-service.update', $ds), [
            'name' => 'New',
            'icon' => 'IconClock',
            'hex_color' => '#abcdef',
        ]);

        $response->assertOk();
        $this->assertSame('New', $ds->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $ds = DayService::factory()->create();

        $response = $this->delete(route('day-service.destroy', $ds));

        $response->assertOk();
        $this->assertDatabaseMissing('day_services', ['id' => $ds->id]);
    }
}
