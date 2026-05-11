<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * ShiftPlanRequestChangeController is a scaffold/no-op controller.
 */
final class ShiftPlanRequestChangeControllerTest extends FeatureTestCase
{
    #[Test]
    public function controller_is_resolvable_from_container(): void
    {
        $this->assertInstanceOf(
            \App\Http\Controllers\ShiftPlanRequestChangeController::class,
            $this->app->make(\App\Http\Controllers\ShiftPlanRequestChangeController::class)
        );
    }
}
