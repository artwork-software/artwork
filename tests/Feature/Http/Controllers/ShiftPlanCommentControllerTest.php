<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * ShiftPlanCommentController is a scaffold/no-op controller.
 */
final class ShiftPlanCommentControllerTest extends FeatureTestCase
{
    #[Test]
    public function controller_is_resolvable_from_container(): void
    {
        $this->assertInstanceOf(
            \App\Http\Controllers\ShiftPlanCommentController::class,
            $this->app->make(\App\Http\Controllers\ShiftPlanCommentController::class)
        );
    }
}
