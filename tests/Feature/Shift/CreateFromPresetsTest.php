<?php

namespace Tests\Feature\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Project\Models\Project;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateFromPresetsTest extends TestCase
{
    public function testCreateFromPresetsCreatesShiftsAndRedirects(): void
    {
        if (!Role::where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()) {
            $this->artisan('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        }

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->actingAs($user);

        $room = Room::factory()->create();
        $craft = Craft::factory()->create();

        ShiftQualification::query()->create([
            'icon' => 'briefcase-icon',
            'name' => 'Mitarbeiter',
            'available' => true,
        ]);

        $preset = SingleShiftPreset::query()->create([
            'name' => 'Test Preset',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'break_duration' => 15,
            'craft_id' => $craft->id,
            'description' => 'Preset description',
        ]);

        $initialShiftCount = Shift::query()->count();

        $response = $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2025-12-18',
            'preset_ids' => [$preset->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($initialShiftCount + 1, Shift::query()->count());

        /** @var Shift $shift */
        $shift = Shift::query()->latest('id')->firstOrFail();
        $this->assertSame($room->id, $shift->room_id);
        $this->assertSame($craft->id, $shift->craft_id);
        $this->assertSame('08:00', $shift->start);
        $this->assertSame('12:00', $shift->end);
    }

    public function testCreateFromPresetsCanAssignProjectToCreatedShifts(): void
    {
        if (!Role::where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()) {
            $this->artisan('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        }

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $this->actingAs($user);

        $room = Room::factory()->create();
        $craft = Craft::factory()->create();
        $project = Project::factory()->create();

        ShiftQualification::query()->create([
            'icon' => 'briefcase-icon',
            'name' => 'Mitarbeiter',
            'available' => true,
        ]);

        $preset = SingleShiftPreset::query()->create([
            'name' => 'Test Preset',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'break_duration' => 15,
            'craft_id' => $craft->id,
            'description' => 'Preset description',
        ]);

        $initialShiftCount = Shift::query()->count();

        $response = $this->post(route('shifts.createFromPresets'), [
            'room_id' => $room->id,
            'day' => '2025-12-18',
            'preset_ids' => [$preset->id],
            'project_id' => $project->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($initialShiftCount + 1, Shift::query()->count());

        /** @var Shift $shift */
        $shift = Shift::query()->latest('id')->firstOrFail();
        $this->assertSame($project->id, $shift->project_id);
    }
}
