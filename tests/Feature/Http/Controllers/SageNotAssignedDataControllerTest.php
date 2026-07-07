<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SageNotAssignedDataControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_sage_not_assigned_trashed(): void
    {
        $this->get(route('sageNotAssignedData.trashed'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_sage_not_assigned_trashed(): void
    {
        // Smoke-only: Sage controllers depend on external Sage API; only verify auth + status.
        $this->actingAsAdmin();

        $response = $this->get(route('sageNotAssignedData.trashed'));

        $response->assertOk();
    }
}
