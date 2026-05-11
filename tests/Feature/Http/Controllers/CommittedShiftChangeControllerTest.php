<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\CommittedShiftChange;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CommittedShiftChangeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_acknowledge(): void
    {
        $change = CommittedShiftChange::query()->forceCreate([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'update',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ]);
        $this->post(route('committed-shift-changes.acknowledge', $change))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_acknowledge(): void
    {
        $admin = $this->actingAsAdmin();
        $change = CommittedShiftChange::query()->forceCreate([
            'subject_type' => 'Shift',
            'subject_id' => 1,
            'change_type' => 'update',
            'field_changes' => [],
            'changed_at' => now(),
            'acknowledged_at' => null,
        ]);

        $response = $this->post(route('committed-shift-changes.acknowledge', $change));

        $response->assertRedirect();
        $this->assertNotNull($change->fresh()->acknowledged_at);
    }
}
