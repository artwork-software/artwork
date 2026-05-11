<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetTemplateControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_templates(): void
    {
        $this->get(route('budget-settings.templates'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_templates(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.templates'));

        $response->assertOk();
    }
}
