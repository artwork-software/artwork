<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Barryvdh\Snappy\PdfWrapper;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftPlanPdfExportTest extends FeatureTestCase
{
    #[Test]
    public function guestCannotExportTheShiftPlanPdf(): void
    {
        $this->post(route('shift.plan.export.pdf'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function craftFilterOverrideIsAppliedInMemoryAndStatedInThePdfHeader(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $craftTechnik = Craft::factory()->create(['name' => 'Technik']);
        $craftKasse = Craft::factory()->create(['name' => 'Kassendienst']);
        $room = Room::factory()->create(['name' => 'Halle 1']);

        $shiftTechnik = Shift::factory()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'craft_id' => $craftTechnik->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start' => '08:00',
            'end' => '16:00',
        ]);
        $shiftKasse = Shift::factory()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'craft_id' => $craftKasse->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start' => '10:00',
            'end' => '18:00',
        ]);

        // Gespeicherter Filter zeigt auf das andere Gewerk: der Export-Override
        // muss gewinnen, ohne den gespeicherten Filter zu verändern.
        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'craft_ids' => [$craftKasse->id],
        ]);

        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('pdf.shiftplan_export', Mockery::on(
                function (array $data) use ($shiftTechnik, $shiftKasse): bool {
                    $this->assertSame(['Technik'], $data['activeFilter']['crafts']);
                    $this->assertSame('KW 29/2026', $data['kwRange']);
                    $this->assertSame('13.07.2026', $data['startDate']);
                    $this->assertSame('19.07.2026', $data['endDate']);

                    $serializedRooms = json_encode($data['roomLookup']);
                    $this->assertStringContainsString('"Technik"', $serializedRooms);
                    $this->assertStringNotContainsString('"Kassendienst"', $serializedRooms);

                    return true;
                }
            ))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('save')->once();
        $this->app->instance(PdfWrapper::class, $pdf);

        $this->post(route('shift.plan.export.pdf'), [
            'title' => 'Schichtplan',
            'start' => '2026-07-13',
            'end' => '2026-07-19',
            'isInProjectView' => false,
            'isDailyView' => false,
            'craft_ids' => [$craftTechnik->id],
            'paperSize' => 'a4',
            'paperOrientation' => 'landscape',
            'dpi' => 96,
        ])->assertRedirect();

        $storedFilter = $user->userFilters()
            ->where('filter_type', UserFilterTypes::SHIFT_FILTER->value)
            ->first();
        $this->assertSame([$craftKasse->id], $storedFilter->craft_ids);
    }

    #[Test]
    public function emptyCraftOverrideExportsAllCraftsWithoutTouchingTheStoredFilter(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $craft = Craft::factory()->create(['name' => 'Technik']);
        $room = Room::factory()->create(['name' => 'Halle 1']);
        Shift::factory()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'craft_ids' => [$craft->id],
        ]);

        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->once()
            ->with('pdf.shiftplan_export', Mockery::on(function (array $data): bool {
                // Leeres craft_ids-Array = keine Einschränkung, keine Filterzeile.
                $this->assertSame([], $data['activeFilter']['crafts']);

                return true;
            }))
            ->andReturnSelf();
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('save')->once();
        $this->app->instance(PdfWrapper::class, $pdf);

        $this->post(route('shift.plan.export.pdf'), [
            'title' => 'Schichtplan',
            'start' => '2026-07-13',
            'end' => '2026-07-19',
            'isInProjectView' => false,
            'isDailyView' => false,
            'craft_ids' => [],
            'paperSize' => 'a4',
            'paperOrientation' => 'landscape',
            'dpi' => 96,
        ])->assertRedirect();

        $storedFilter = $user->userFilters()
            ->where('filter_type', UserFilterTypes::SHIFT_FILTER->value)
            ->first();
        $this->assertSame([$craft->id], $storedFilter->craft_ids);
    }
}
