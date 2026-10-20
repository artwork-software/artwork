<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Nachträge zum Raumzugriff über Teams (Prod-Review 20.10.2026): Raumlisten fürs Frontend, Nachrichten zu
 * Raumanfragen und die Sperre, sich über die Teampflege selbst zum Raumadmin zu machen.
 */
final class RoomTeamAdminFollowUpTest extends FeatureTestCase
{
    #[Test]
    public function slim_room_lists_contain_direct_and_team_admins(): void
    {
        $room = Room::factory()->create();
        $directAdmin = User::factory()->create();
        $member = User::factory()->create();
        $room->users()->attach($directAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);

        $entry = Room::listWithEffectiveAdminIds(['id', 'name'])->firstWhere('id', $room->id);

        $this->assertSame($room->name, $entry['name']);
        $this->assertEqualsCanonicalizing(
            [$directAdmin->id, $member->id],
            array_column($entry['admins'], 'id')
        );
        $this->assertArrayNotHasKey('creator', $entry);
    }

    #[Test]
    public function messages_on_room_requests_reach_members_of_the_admin_team(): void
    {
        $requester = User::factory()->create();
        $member = User::factory()->create();
        $room = Room::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'user_id' => $requester->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);

        $this->actingAs($requester);
        $this->post(route('event.answer', $event), ['comment' => 'Geht auch eine Stunde später?'])
            ->assertSuccessful();

        $sent = Notification::sent($member, RoomRequestNotification::class)
            ->filter(fn (RoomRequestNotification $notification): bool =>
                $notification->toArray()->type === NotificationEnum::NOTIFICATION_ROOM_REQUEST);
        $this->assertCount(1, $sent);
    }

    #[Test]
    public function team_managers_without_room_rights_cannot_change_members_of_an_admin_team(): void
    {
        $teamManager = $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $room = Room::factory()->create();
        $adminTeam = Department::factory()->create();
        $room->departments()->attach($adminTeam->id, ['is_admin' => true, 'can_request' => false]);

        $this->patch(route('departments.edit', $adminTeam), [
            'name' => $adminTeam->name,
            'svg_name' => $adminTeam->svg_name,
            'users' => [['id' => $teamManager->id]],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, $adminTeam->users()->count());
        $this->assertFalse($room->isAdministeredBy($teamManager));
    }

    #[Test]
    public function team_managers_still_manage_teams_without_room_admin_access(): void
    {
        $teamManager = $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $team = Department::factory()->create();
        $room = Room::factory()->create();
        $room->departments()->attach($team->id, ['is_admin' => false, 'can_request' => true]);

        $this->patch(route('departments.edit', $team), [
            'name' => $team->name,
            'svg_name' => $team->svg_name,
            'users' => [['id' => $teamManager->id]],
        ])->assertRedirect()->assertSessionMissing('error');

        $this->assertSame([$teamManager->id], $team->users()->pluck('users.id')->all());
    }

    #[Test]
    public function room_managers_may_change_members_of_an_admin_team(): void
    {
        $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE, PermissionEnum::ROOM_UPDATE]);
        $member = User::factory()->create();
        $room = Room::factory()->create();
        $adminTeam = Department::factory()->create();
        $room->departments()->attach($adminTeam->id, ['is_admin' => true, 'can_request' => false]);

        $this->patch(route('departments.edit', $adminTeam), [
            'name' => $adminTeam->name,
            'svg_name' => $adminTeam->svg_name,
            'users' => [['id' => $member->id]],
        ])->assertRedirect()->assertSessionMissing('error');

        $this->assertTrue($room->isAdministeredBy($member));
    }

    #[Test]
    public function removing_all_members_of_an_admin_team_needs_room_rights(): void
    {
        $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $member = User::factory()->create();
        $room = Room::factory()->create();
        $adminTeam = Department::factory()->create();
        $adminTeam->users()->attach($member->id);
        $room->departments()->attach($adminTeam->id, ['is_admin' => true, 'can_request' => false]);

        $this->patch(route('departments.remove.members', $adminTeam))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($room->isAdministeredBy($member));
    }

    #[Test]
    public function the_profile_keeps_admin_team_memberships_for_people_without_room_rights(): void
    {
        $teamManager = $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $room = Room::factory()->create();
        $adminTeam = Department::factory()->create();
        $room->departments()->attach($adminTeam->id, ['is_admin' => true, 'can_request' => false]);
        $plainTeam = Department::factory()->create();

        $this->patch(route('user.update', $teamManager), [
            'first_name' => $teamManager->first_name,
            'last_name' => $teamManager->last_name,
            'departments' => [['id' => $adminTeam->id], ['id' => $plainTeam->id]],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertEqualsCanonicalizing(
            [$plainTeam->id],
            $teamManager->departments()->pluck('departments.id')->all()
        );
        $this->assertFalse($room->isAdministeredBy($teamManager));
    }

    #[Test]
    public function deleting_an_admin_team_clears_the_cached_request_flag_of_its_members(): void
    {
        $this->actingAsUserWith([PermissionEnum::TEAM_UPDATE]);
        $member = User::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        Room::factory()->create()->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);
        Cache::put("user:{$member->id}:can_see_incoming_requests", true, 300);

        $this->delete(route('departments.destroy', $team))->assertRedirect();

        $this->assertFalse(Cache::has("user:{$member->id}:can_see_incoming_requests"));
    }

    #[Test]
    public function changing_room_access_without_rights_is_forbidden_before_validation(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Room::factory()->create();

        $this->patchJson(route('room.users.update', $room), [
            'users' => [],
            'departments' => [['id' => 999999, 'is_admin' => true, 'can_request' => false]],
        ])->assertForbidden();
    }
}
