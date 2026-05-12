<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectPrintLayoutControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_print_layout_index(): void
    {
        $this->get(route('project-print-layout.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_project_print_layout_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('project-print-layout.index'));

        $response->assertOk();
    }
}
