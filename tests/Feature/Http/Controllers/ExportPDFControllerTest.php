<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Calendar\Services\EventExportDisplaySettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
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
        // shift.plan.export.pdf verlangt seit Block 1a 'can view shift plan'
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
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
        // shift.plan.export.pdf verlangt seit Block 1a 'can view shift plan'
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
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
        // shift.plan.export.pdf verlangt seit Block 1a 'can view shift plan'
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
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

    #[Test]
    public function calendar_pdf_without_saved_filter_and_payload_uses_the_default_period(): void
    {
        // Ohne Kalenderfilter und ohne „filter“ im Request gab es vorher einen TypeError (500).
        $user = $this->actingAsAdmin();
        $user->userFilters()->delete();
        $this->mockSnappyPdf($capturedViewData);

        $this->post(route('calendar.export.pdf'))->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
    }

    #[Test]
    public function calendar_pdf_rejects_unknown_projects_and_invalid_dates(): void
    {
        $this->actingAsAdmin();
        $this->mockSnappyPdf($capturedViewData);

        $this->post(route('calendar.export.pdf'), ['project' => 999999999, 'start' => 'kein-datum'])
            ->assertSessionHasErrors(['project', 'start']);
        $this->post(route('calendar.export.monthly-pdf'), ['project' => 999999999, 'filter' => 'x'])
            ->assertSessionHasErrors(['project', 'filter']);

        self::assertNull($capturedViewData);
    }

    #[Test]
    public function monthly_overview_shows_identical_type_and_names_only_once(): void
    {
        // Kundenfall: Terminart-Kürzel = Name ("TE TE") und Terminname = Projektname
        $eventTypeAbbreviationOnly = (object) ['abbreviation' => 'TE', 'name' => 'TE', 'hex_code' => '#A7A6B1'];
        $eventTypeDistinct = (object) ['abbreviation' => 'Ab', 'name' => 'Abbau', 'hex_code' => '#A7A6B1'];
        $project = (object) ['name' => 'Love is a Verb #2'];
        $room = (object) ['id' => 1, 'name' => 'HAU1'];

        $html = view('pdf.calendarMonthlyOverview', [
            'title' => 'Monatsübersicht',
            'rooms' => [$room],
            'calendarLookup' => [
                1 => ['01.10.2026' => ['events' => [
                    (object) [
                        'start' => '2026-10-01 10:00:00',
                        'end' => '2026-10-01 20:00:00',
                        'eventType' => $eventTypeAbbreviationOnly,
                        'project' => $project,
                        'eventName' => 'Love is a Verb #2',
                    ],
                    (object) [
                        'start' => '2026-10-01 21:00:00',
                        'end' => '2026-10-01 22:00:00',
                        'eventType' => $eventTypeDistinct,
                        'project' => $project,
                        'eventName' => 'Bühne leeren',
                    ],
                ]]],
            ],
            'pages' => [[
                'monthLabel' => 'Oktober 2026',
                'days' => [['fullDay' => '01.10.2026', 'display' => 'Do, 01.10', 'isWeekend' => false]],
            ]],
            'created_by' => 'Test',
            'created_date' => '27.10.2026',
            'bigLogoBase64' => null,
            'display' => EventExportDisplaySettings::fromRequest(null, null),
            'paperSize' => 'a3',
        ])->render();

        self::assertStringContainsString('10:00-20:00 TE', $html);
        self::assertStringNotContainsString('TE TE', $html);
        self::assertSame(1, substr_count($html, 'Love is a Verb #2,'));
        self::assertStringContainsString('Love is a Verb #2, Bühne leeren', $html);
        self::assertStringContainsString('21:00-22:00 Ab Abbau', $html);
    }
}
