<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ExportPDFControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_create_pdf(): void
    {
        // Smoke-only: ExportPDF generates binary content; verify auth gate.
        $this->post(route('calendar.export.pdf'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_create_monthly_pdf(): void
    {
        $this->post(route('calendar.export.monthly-pdf'))
            ->assertRedirect(route('login'));
    }
}
