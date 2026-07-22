<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserShiftPlanPdfExportTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')->once()->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('save')->once()->andReturnSelf();

        $this->app->instance(PdfWrapper::class, $pdf);
    }

    #[Test]
    public function export_works_for_internal_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('user.shiftplan.export.monthly-pdf', $user->id), [
            'type' => 'user',
            'model_id' => $user->id,
            'startMonth' => today()->format('Y-m'),
            'endMonth' => today()->format('Y-m'),
        ])->assertRedirect();
    }

    #[Test]
    public function export_works_for_service_provider(): void
    {
        $this->actingAsAdmin();
        $serviceProvider = ServiceProvider::factory()->create();

        $this->post(route('user.shiftplan.export.monthly-pdf', $serviceProvider->id), [
            'type' => 'service_provider',
            'model_id' => $serviceProvider->id,
            'startMonth' => today()->format('Y-m'),
            'endMonth' => today()->format('Y-m'),
        ])->assertRedirect();
    }
}
