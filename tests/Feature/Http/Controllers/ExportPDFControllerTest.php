<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Mockery;
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

    #[Test]
    public function guest_cannot_create_shift_plan_pdf(): void
    {
        $this->post(route('shift.plan.export.pdf'))
            ->assertRedirect(route('login'));
    }

    /**
     * Replace the wkhtmltopdf wrapper so no binary is required; captures the view data.
     *
     * @param array<string, mixed>|null $capturedViewData
     */
    private function mockSnappyPdf(?array &$capturedViewData): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->andReturnUsing(function (string $view, array $data) use (&$capturedViewData, $pdf) {
                $capturedViewData = $data;

                return $pdf;
            });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOption')->andReturnSelf();
        $pdf->shouldReceive('save')->andReturnSelf();

        $this->app->instance(PdfWrapper::class, $pdf);
    }

    #[Test]
    public function shift_plan_pdf_applies_request_filters_without_persisting_them(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);
        $otherRoom = Room::factory()->create(['user_id' => $user->id]);

        $savedFilter = $user->userFilters()->create([
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'room_ids' => [$otherRoom->id],
            'craft_ids' => [999],
        ]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('shift.plan.export.pdf'), [
                'start' => '2026-08-10',
                'end' => '2026-08-16',
                'isInProjectView' => false,
                'isDailyView' => false,
                'room_ids' => [$room->id],
                'craft_ids' => [],
                'hideEmptyRooms' => false,
            ])
            ->assertRedirectContains("download");

        // The export renders exactly the requested room, not the saved filter's room.
        self::assertNotNull($capturedViewData);
        self::assertSame([$room->name], $capturedViewData['activeFilter']['rooms']);
        self::assertSame([], $capturedViewData['activeFilter']['crafts']);
        self::assertSame('10.08.2026', $capturedViewData['startDate']);
        self::assertSame('16.08.2026', $capturedViewData['endDate']);
        self::assertCount(1, $capturedViewData['weeks']);

        // The user's saved shift plan filter must stay untouched by export overrides.
        $savedFilter->refresh();
        self::assertSame([$otherRoom->id], $savedFilter->room_ids);
        self::assertSame([999], $savedFilter->craft_ids);
    }

    #[Test]
    public function shift_plan_pdf_caps_period_at_six_months(): void
    {
        $user = User::factory()->create();
        Room::factory()->create(['user_id' => $user->id]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('shift.plan.export.pdf'), [
                'start' => '2026-01-01',
                'end' => '2027-06-30',
                'isInProjectView' => false,
                'isDailyView' => false,
            ])
            ->assertRedirectContains("download");

        self::assertNotNull($capturedViewData);
        self::assertSame('01.01.2026', $capturedViewData['startDate']);
        self::assertSame('03.07.2026', $capturedViewData['endDate']);
    }

    #[Test]
    public function shift_plan_pdf_can_hide_rooms_without_content(): void
    {
        $user = User::factory()->create();
        Room::factory()->create(['user_id' => $user->id]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('shift.plan.export.pdf'), [
                'start' => '2026-08-10',
                'end' => '2026-08-16',
                'isInProjectView' => false,
                'isDailyView' => false,
                'hideEmptyRooms' => true,
            ])
            ->assertRedirectContains("download");

        self::assertNotNull($capturedViewData);
        self::assertCount(0, $capturedViewData['rooms']);
        self::assertTrue($capturedViewData['hideEmptyRooms']);
    }
}
