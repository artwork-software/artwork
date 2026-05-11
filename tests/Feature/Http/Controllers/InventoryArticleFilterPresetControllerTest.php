<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * InventoryArticleFilterPresetController is currently empty (placeholder).
 * Only a container resolution smoke test is possible.
 */
final class InventoryArticleFilterPresetControllerTest extends FeatureTestCase
{
    #[Test]
    public function controller_is_resolvable_from_container(): void
    {
        $this->assertInstanceOf(
            \App\Http\Controllers\InventoryArticleFilterPresetController::class,
            $this->app->make(\App\Http\Controllers\InventoryArticleFilterPresetController::class)
        );
    }
}
