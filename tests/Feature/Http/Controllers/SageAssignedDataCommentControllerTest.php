<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SageAssignedDataCommentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_sage_assigned_data_comment(): void
    {
        // Smoke-only: Sage controllers depend on external Sage API; only verify auth gate.
        $this->post(route('sageAssignedDataComments.store'), [])
            ->assertRedirect(route('login'));
    }
}
