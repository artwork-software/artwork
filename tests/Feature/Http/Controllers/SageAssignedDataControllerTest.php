<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\SageAssignedData;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SageAssignedDataControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy_sage_assigned_data(): void
    {
        // Smoke-only: Sage controllers depend on external Sage API; only verify auth gate.
        // Use bogus ID since route does not actually need a model resolution for redirect-to-login check.
        $this->delete('/project/budget/sageAssignedData/999999')
            ->assertRedirect(route('login'));
    }
}
