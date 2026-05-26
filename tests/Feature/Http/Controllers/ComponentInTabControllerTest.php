<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * ComponentInTabController is a scaffold/no-op controller with no business logic.
 * Only minimal smoke test possible — no routes target it directly except via resource.
 */
final class ComponentInTabControllerTest extends FeatureTestCase
{
    #[Test]
    public function controller_is_resolvable_from_container(): void
    {
        $this->assertInstanceOf(
            \App\Http\Controllers\ComponentInTabController::class,
            $this->app->make(\App\Http\Controllers\ComponentInTabController::class)
        );
    }
}
