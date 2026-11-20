<?php

namespace Tests\Feature\Modules\Trash;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Trash\Models\TrashRecord;
use Artwork\Modules\Trash\Services\TrashInfoService;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Papierkorb: Zahl je Tab, Löschinformationen (wann, von wem, verbleibende Tage) und die
 * flache Raumliste.
 */
final class TrashOverviewTest extends TestCase
{
    #[Test]
    public function counts_cover_only_the_tabs_the_user_may_open(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $roomsBefore = Room::onlyTrashed()->count();
        $settingsBefore = Genre::onlyTrashed()->count();
        Room::factory()->create()->delete();
        Genre::create(['name' => 'Zählprobe'])->delete();

        $counts = $this->getJson(route('trash.counts'))->assertOk()->json('counts');

        $this->assertSame($roomsBefore + 1, $counts['Trash/Rooms']);
        $this->assertGreaterThanOrEqual($settingsBefore + 1, $counts['Trash/ProjectSettings']);
        $this->assertArrayHasKey('Trash/Projects', $counts);
        $this->assertArrayNotHasKey('Trash/Shifts', $counts);
        $this->assertArrayNotHasKey('Trash/CrmContacts', $counts);
        $this->assertArrayNotHasKey('Trash/InventoryArticles', $counts);
        $this->assertArrayNotHasKey('Trash/BudgetManagementAccount', $counts);
    }

    #[Test]
    public function counts_are_empty_without_any_trash_right(): void
    {
        $this->actingAsUserWith([]);

        $this->getJson(route('trash.counts'))->assertOk()->assertExactJson(['counts' => []]);
    }

    #[Test]
    public function guests_get_no_counts(): void
    {
        $this->getJson(route('trash.counts'))->assertUnauthorized();
    }

    #[Test]
    public function deleting_records_who_deleted_and_restoring_forgets_it(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $room = Room::factory()->create();

        $room->delete();

        $this->assertDatabaseHas('trash_records', [
            'subject_type' => $room->getMorphClass(),
            'subject_id' => $room->id,
            'user_id' => $user->id,
        ]);

        $room->restore();

        $this->assertSame(0, TrashRecord::where('subject_id', $room->id)
            ->where('subject_type', $room->getMorphClass())->count());
    }

    #[Test]
    public function force_deleting_forgets_who_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $genre = Genre::create(['name' => 'Protokollprobe']);
        $genre->delete();
        $this->assertSame(1, TrashRecord::where('subject_type', $genre->getMorphClass())
            ->where('subject_id', $genre->id)->count());

        $genre->forceDelete();

        $this->assertSame(0, TrashRecord::where('subject_type', $genre->getMorphClass())
            ->where('subject_id', $genre->id)->count());
    }

    #[Test]
    public function nothing_is_recorded_without_a_logged_in_user(): void
    {
        $room = Room::factory()->create();

        $room->delete();

        $this->assertSame(0, TrashRecord::where('subject_type', $room->getMorphClass())
            ->where('subject_id', $room->id)->count());
    }

    #[Test]
    public function trashed_rooms_are_listed_one_per_row_with_deletion_info(): void
    {
        $user = $this->actingAsAdmin();
        Room::onlyTrashed()->forceDelete();
        $area = Area::factory()->create(['name' => 'Papierkorb-Areal']);
        $room = Room::factory()->create(['name' => 'Papierkorb-Raum', 'area_id' => $area->id]);
        $room->delete();

        $this->get(route('rooms.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trash/Rooms')
                ->where('trashed_rooms.total', 1)
                ->where('trashed_rooms.data.0.name', 'Papierkorb-Raum')
                ->where('trashed_rooms.data.0.area_name', 'Papierkorb-Areal')
                ->where('trashed_rooms.data.0.area_trashed', false)
                ->where('trashed_rooms.data.0.trash.days_left', TrashInfoService::RETENTION_DAYS)
                ->where('trashed_rooms.data.0.trash.deleted_by', trim($user->first_name . ' ' . $user->last_name))
                ->has('trashed_rooms.data.0.trash.deleted_at'));
    }

    #[Test]
    public function room_search_also_matches_the_area_name(): void
    {
        $this->actingAsAdmin();
        Room::onlyTrashed()->forceDelete();
        $area = Area::factory()->create(['name' => 'Nordflügel']);
        Room::factory()->create(['name' => 'Probebühne', 'area_id' => $area->id])->delete();
        Room::factory()->create(['name' => 'Werkstatt'])->delete();

        $this->get(route('rooms.trashed', ['search' => 'Nordfl']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trashed_rooms.total', 1)
                ->where('trashed_rooms.data.0.name', 'Probebühne'));
    }

    #[Test]
    public function a_room_of_a_trashed_area_is_marked(): void
    {
        $this->actingAsAdmin();
        Room::onlyTrashed()->forceDelete();
        $area = Area::factory()->create();
        Room::factory()->create(['area_id' => $area->id]);
        $area->delete();

        $this->get(route('rooms.trashed'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trashed_rooms.data.0.area_trashed', true));
    }

    #[Test]
    public function trashed_areas_list_their_room_names_and_deletion_info(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        Area::onlyTrashed()->get()->each->forceDelete();
        $area = Area::factory()->create(['name' => 'Altes Areal']);
        Room::factory()->create(['name' => 'Saal A', 'area_id' => $area->id]);
        $area->delete();

        $this->get(route('areas.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trashed_areas.data.0.name', 'Altes Areal')
                ->where('trashed_areas.data.0.room_names', ['Saal A'])
                ->has('trashed_areas.data.0.trash.deleted_at'));
    }

    #[Test]
    public function trashed_project_settings_carry_deletion_info(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $genre = Genre::create(['name' => 'Infoprobe']);
        $genre->delete();

        $this->get(route('projects.settings.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trashed_genres.0.name', 'Infoprobe')
                ->where('trashed_genres.0.trash.days_left', TrashInfoService::RETENTION_DAYS));
    }

    #[Test]
    public function the_daily_cleanup_drops_outdated_deleted_by_notes(): void
    {
        $old = TrashRecord::create(['subject_type' => 'probe', 'subject_id' => 1, 'user_id' => null]);
        $old->forceFill(['updated_at' => now()->subDays(TrashInfoService::RETENTION_DAYS * 3 + 1)])->saveQuietly();
        $recent = TrashRecord::create(['subject_type' => 'probe', 'subject_id' => 2, 'user_id' => null]);

        $this->artisan('artwork:purge-trash')->run();

        $this->assertNull(TrashRecord::find($old->id));
        $this->assertNotNull(TrashRecord::find($recent->id));
    }

    #[Test]
    public function days_left_counts_down_to_zero(): void
    {
        $deletedAt = Carbon::parse('2026-11-01 10:00:00');

        $this->assertSame(30, TrashInfoService::daysLeft($deletedAt, Carbon::parse('2026-11-01 10:00:00')));
        $this->assertSame(29, TrashInfoService::daysLeft($deletedAt, Carbon::parse('2026-11-02 10:00:00')));
        $this->assertSame(1, TrashInfoService::daysLeft($deletedAt, Carbon::parse('2026-11-30 12:00:00')));
        $this->assertSame(0, TrashInfoService::daysLeft($deletedAt, Carbon::parse('2026-12-01 10:00:00')));
        $this->assertSame(0, TrashInfoService::daysLeft($deletedAt, Carbon::parse('2026-12-24 10:00:00')));
    }

    #[Test]
    public function rooms_trashed_with_their_area_get_the_same_deleted_by_note(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $area = Area::factory()->create();
        $room = Room::factory()->create(['area_id' => $area->id]);

        $area->delete();

        $this->assertSoftDeleted($room);
        $this->assertDatabaseHas('trash_records', [
            'subject_type' => $room->getMorphClass(),
            'subject_id' => $room->id,
            'user_id' => $user->id,
        ]);

        $area->restore();

        $this->assertDatabaseMissing('trash_records', [
            'subject_type' => $room->getMorphClass(),
            'subject_id' => $room->id,
        ]);
    }

    #[Test]
    public function the_daily_cleanup_keeps_notes_of_entries_that_are_still_in_the_trash(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $stillTrashed = Genre::create(['name' => 'Bleibt im Papierkorb']);
        $stillTrashed->delete();
        $gone = Genre::create(['name' => 'Ist weg']);
        $gone->delete();
        $old = now()->subDays(TrashInfoService::RETENTION_DAYS * 3 + 1);
        TrashRecord::query()->where('subject_type', $gone->getMorphClass())
            ->whereIn('subject_id', [$stillTrashed->id, $gone->id])
            ->update(['updated_at' => $old]);
        // ohne Modell-Events gelöscht, wie bei "Alle löschen"
        Genre::withTrashed()->whereKey($gone->id)->forceDelete();
        // frisch gelöscht, damit die Bereinigung den Eintrag selbst noch nicht entfernt
        Genre::withTrashed()->whereKey($stillTrashed->id)->update(['deleted_at' => now()]);

        $this->artisan('artwork:purge-trash')->run();

        $this->assertDatabaseHas('trash_records', [
            'subject_type' => $stillTrashed->getMorphClass(),
            'subject_id' => $stillTrashed->id,
        ]);
        $this->assertDatabaseMissing('trash_records', [
            'subject_type' => $gone->getMorphClass(),
            'subject_id' => $gone->id,
        ]);
    }
}
