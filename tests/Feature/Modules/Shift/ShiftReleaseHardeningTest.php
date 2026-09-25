<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\DestroyShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftDeletionService;
use Artwork\Modules\Shift\Services\ShiftService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Absicherungen aus dem Release-Review: Termin-Wiederherstellung, Massenlöschen, Funktion löschen.
 */
final class ShiftReleaseHardeningTest extends FeatureTestCase
{
    private function eventShift(Event $event): Shift
    {
        return Shift::factory()->create([
            'event_id' => $event->id,
            'room_id' => $event->room_id,
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ]);
    }

    #[Test]
    public function restoring_an_event_does_not_bring_back_individually_deleted_shifts(): void
    {
        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id, 'project_id' => null]);
        $deletedEarlier = $this->eventShift($event);
        $deletedWithEvent = $this->eventShift($event);

        // S1 wurde vor Tagen einzeln gelöscht
        $deletedEarlier->delete();
        DB::table('shifts')->where('id', $deletedEarlier->id)->update(['deleted_at' => now()->subDays(3)]);

        // Termin samt restlicher Schicht löschen …
        $deletedWithEvent->delete();
        $event->delete();

        $trashed = app(ShiftService::class)->trashedWithEvent($event->fresh(), Event::withTrashed()->find($event->id)->deleted_at);

        $this->assertSame([$deletedWithEvent->id], $trashed->pluck('id')->all());
    }

    #[Test]
    public function event_restore_uses_the_time_window(): void
    {
        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id, 'project_id' => null]);
        $deletedEarlier = $this->eventShift($event);
        $deletedWithEvent = $this->eventShift($event);
        $deletedEarlier->delete();
        DB::table('shifts')->where('id', $deletedEarlier->id)->update(['deleted_at' => now()->subDays(3)]);
        $deletedWithEvent->delete();
        $event->delete();

        app()->call([app(EventService::class), 'restoreAll'], ['events' => [Event::withTrashed()->find($event->id)]]);

        $this->assertNotSoftDeleted('shifts', ['id' => $deletedWithEvent->id]);
        $this->assertSoftDeleted('shifts', ['id' => $deletedEarlier->id]);
    }

    #[Test]
    public function mass_deletion_without_per_shift_broadcasts(): void
    {
        EventFacade::fake([DestroyShift::class]);
        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id, 'project_id' => null]);
        $shifts = collect([$this->eventShift($event), $this->eventShift($event)]);

        $payloads = app(ShiftDeletionService::class)->deleteMany($shifts, true, false);

        $this->assertSame([], $payloads);
        EventFacade::assertNotDispatched(DestroyShift::class);
        $shifts->each(fn (Shift $shift) => $this->assertSoftDeleted('shifts', ['id' => $shift->id]));
    }

    #[Test]
    public function deleting_a_function_moves_active_slots_into_an_active_default_slot(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => Craft::factory()->create()->id,
        ]);
        $function = ShiftQualification::factory()->create();
        ShiftsQualifications::query()->create(['shift_id' => $shift->id, 'shift_qualification_id' => $function->id, 'value' => 2]);
        // früher einzeln entfernter Standard-Platz liegt im Papierkorb
        $trashedDefault = ShiftsQualifications::query()->create(['shift_id' => $shift->id, 'shift_qualification_id' => 1, 'value' => 1]);
        $trashedDefault->delete();

        $this->delete(route('shift-qualifications.destroy', $function));

        $active = ShiftsQualifications::query()
            ->where('shift_id', $shift->id)
            ->where('shift_qualification_id', 1)
            ->get();
        $this->assertCount(1, $active);
        $this->assertSame(2, (int) $active->first()->value);
    }
}
