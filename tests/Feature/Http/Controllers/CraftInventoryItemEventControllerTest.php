<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CraftInventoryItemEventControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_multiple(): void
    {
        $this->post(route('inventory.multi.events.store'), ['events' => []])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_store_multiple_validates_events_required(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('inventory.multi.events.store'), [
            'events' => [],
        ]);

        $response->assertSessionHasErrors('events');
    }
}
