<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Policies\EventPolicy;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Raumzugriff über Teams: alle Mitglieder eines Teams gelten als Raumadmins bzw. dürfen anfragen.
 */
final class RoomTeamAccessTest extends FeatureTestCase
{
    #[Test]
    public function room_access_can_be_given_to_teams(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $person = User::factory()->create();
        $adminTeam = Department::factory()->create();
        $requestTeam = Department::factory()->create();

        $this->patch(route('room.users.update', $room), [
            'users' => [['id' => $person->id, 'is_admin' => false, 'can_request' => true]],
            'departments' => [
                ['id' => $adminTeam->id, 'is_admin' => true, 'can_request' => false],
                ['id' => $requestTeam->id, 'is_admin' => false, 'can_request' => true],
            ],
        ])->assertRedirect();

        $this->assertSame([$adminTeam->id], $room->adminDepartments()->pluck('departments.id')->all());
        $this->assertSame([$requestTeam->id], $room->requestableByDepartments()->pluck('departments.id')->all());
        $this->assertSame([$person->id], $room->requestableBy()->pluck('users.id')->all());
    }

    #[Test]
    public function the_former_payload_with_people_only_still_works_and_keeps_teams(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $team = Department::factory()->create();
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);
        $person = User::factory()->create();

        $this->patch(route('room.users.update', $room), [
            ['id' => $person->id, 'is_admin' => true, 'can_request' => false],
        ])->assertRedirect();

        $this->assertSame([$person->id], $room->admins()->pluck('users.id')->all());
        $this->assertSame([$team->id], $room->adminDepartments()->pluck('departments.id')->all());
    }

    #[Test]
    public function members_of_an_admin_team_manage_the_room(): void
    {
        $room = Room::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);

        $this->assertTrue($room->isAdministeredBy($member));
        $this->assertFalse($room->isAdministeredBy($outsider));
        $this->assertTrue($member->can('update', $room));
        $this->assertFalse($outsider->can('update', $room));
        $this->assertContains($member->id, $room->fresh()->effectiveAdminIds());
        $this->assertSame([$room->id], Room::query()->administeredBy($member->id)->pluck('id')->all());

        $this->actingAs($member);
        $this->get(route('rooms.show', $room))->assertOk();
    }

    #[Test]
    public function members_of_an_admin_team_book_directly_and_answer_room_requests(): void
    {
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $member = User::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);
        $request = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);

        $policy = app(EventPolicy::class);
        $this->assertTrue($policy->book($member, $room));
        $this->assertTrue($policy->answerRoomRequest($member, $request));
        $this->assertFalse($policy->answerRoomRequest(User::factory()->create(), $request));
    }

    #[Test]
    public function members_of_a_request_team_may_request_but_not_book_directly(): void
    {
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $member = User::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => false, 'can_request' => true]);

        $policy = app(EventPolicy::class);
        $this->assertTrue($policy->book($member, $room, true));
        $this->assertFalse($policy->book($member, $room));
        $this->assertContains($member->id, $room->fresh()->effectiveRequestableByIds());
    }

    #[Test]
    public function a_room_with_an_admin_team_no_longer_falls_back_to_its_creator(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $creator->id]);
        $request = Event::factory()->create(['room_id' => $room->id, 'occupancy_option' => true, 'is_planning' => false]);

        $this->assertTrue(app(EventPolicy::class)->answerRoomRequest($creator, $request));

        $team = Department::factory()->create();
        $team->users()->attach(User::factory()->create()->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => false]);

        $this->assertFalse(app(EventPolicy::class)->answerRoomRequest($creator, $request->fresh()));
    }

    #[Test]
    public function adding_an_admin_team_is_recorded_and_clears_the_cached_request_flag_of_its_members(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $member = User::factory()->create();
        $team = Department::factory()->create(['name' => 'Technik']);
        $team->users()->attach($member->id);
        Cache::put("user:{$member->id}:can_see_incoming_requests", false, 300);

        $this->patch(route('room.users.update', $room), [
            'users' => [],
            'departments' => [['id' => $team->id, 'is_admin' => true, 'can_request' => false]],
        ])->assertRedirect();

        $this->assertFalse(Cache::has("user:{$member->id}:can_see_incoming_requests"));
        $history = json_encode(app(ChangeService::class)->historyFor($room));
        $this->assertStringContainsString('Added as room admin', $history);
        $this->assertStringContainsString('Technik', $history);
    }

    #[Test]
    public function room_payloads_list_team_members_as_room_admins(): void
    {
        $room = Room::factory()->create();
        $member = User::factory()->create();
        $team = Department::factory()->create();
        $team->users()->attach($member->id);
        $room->departments()->attach($team->id, ['is_admin' => true, 'can_request' => true]);

        $loaded = Room::query()
            ->whereKey($room->id)
            ->with('adminDepartments.users:id,first_name,last_name,profile_photo_path')
            ->get();
        $payload = RoomIndexWithoutEventsResource::collection($loaded)->resolve()[0];

        $this->assertContains($member->id, array_column($payload['admins'], 'id'));
        $this->assertContains($member->id, $room->fresh()->effectiveRequestableByIds());
    }

    #[Test]
    public function people_without_room_rights_cannot_change_room_access(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Room::factory()->create();
        $team = Department::factory()->create();

        $this->patch(route('room.users.update', $room), [
            'users' => [],
            'departments' => [['id' => $team->id, 'is_admin' => true, 'can_request' => false]],
        ])->assertForbidden();

        $this->assertSame(0, $room->departments()->count());
    }

    #[Test]
    public function team_payload_is_validated(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();

        $this->patchJson(route('room.users.update', $room), [
            'users' => [],
            'departments' => [['id' => 999999, 'is_admin' => true, 'can_request' => false]],
        ])->assertUnprocessable();
    }
}
