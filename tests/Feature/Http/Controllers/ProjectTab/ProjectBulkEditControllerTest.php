<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectBulkEditControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_bulk_edit_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.bulk-edit', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_bulk_edit_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.bulk-edit', $project));

        $response->assertOk();
    }

    #[Test]
    public function user_without_project_access_is_redirected_back(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.bulk-edit', $project));

        $response->assertStatus(302);
    }

    #[Test]
    public function events_carry_a_slim_room_and_creator_instead_of_full_user_models(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $creator = User::factory()->create(['email' => 'creator-bulk@example.test']);
        $room = Room::factory()->create(['name' => 'Bulk-Raum']);
        $room->admins()->attach(User::factory()->create()->id, ['is_admin' => true]);
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'user_id' => $creator->id,
        ]);

        $event = $this->getJson(route('projects.tabs.bulk-edit', $project))
            ->assertOk()
            ->json('events.0');

        $this->assertSame($room->id, $event['room']['id']);
        $this->assertSame('Bulk-Raum', $event['room']['name']);
        $this->assertArrayHasKey('position', $event['room']);
        $this->assertArrayNotHasKey('admins', $event['room']);
        $this->assertArrayNotHasKey('creator', $event['room']);

        $this->assertArrayNotHasKey('creator', $event);
        $this->assertSame($creator->id, $event['created_by']['id']);
        $this->assertSame($creator->first_name, $event['created_by']['first_name']);
        $this->assertStringNotContainsString('creator-bulk@example.test', json_encode($event));
    }

    #[Test]
    public function calendar_room_filter_does_not_apply_to_project_event_list(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $calendarRoom = Room::factory()->create();
        $otherRoom = Room::factory()->create();
        Event::factory()->create(['project_id' => $project->id, 'room_id' => $calendarRoom->id]);
        Event::factory()->create(['project_id' => $project->id, 'room_id' => $otherRoom->id]);

        $admin->userFilters()->create([
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
            'room_ids' => [$calendarRoom->id],
        ]);

        $response = $this->getJson(route('projects.tabs.bulk-edit', $project))->assertOk();

        $this->assertCount(2, $response->json('events'));
        $this->assertSame(UserFilterTypes::PROJECT_EVENT_LIST_FILTER->value, $response->json('user_filters.filter_type'));
        $this->assertNull($response->json('user_filters.room_ids'));
    }

    #[Test]
    public function project_event_list_uses_its_own_room_filter(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $listRoom = Room::factory()->create();
        $otherRoom = Room::factory()->create();
        Event::factory()->create(['project_id' => $project->id, 'room_id' => $listRoom->id]);
        Event::factory()->create(['project_id' => $project->id, 'room_id' => $otherRoom->id]);

        $this->patch(route('update.user.calendar.filter', $admin), [
            'filter_type' => UserFilterTypes::PROJECT_EVENT_LIST_FILTER->value,
            'room_ids' => [$listRoom->id],
        ])->assertSessionHasNoErrors();

        $events = $this->getJson(route('projects.tabs.bulk-edit', $project))->assertOk()->json('events');

        $this->assertCount(1, $events);
        $this->assertSame($listRoom->id, $events[0]['room_id']);
        $this->assertNull(
            $admin->userFilters()->calendarFilter()->first()?->room_ids,
            'Der Kalenderfilter darf durch den Terminlisten-Filter nicht verändert werden'
        );
    }

    #[Test]
    public function saved_filter_templates_are_separated_between_calendar_and_event_list(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $admin->userFilterTemplates()->create([
            'name' => 'Kalender-Vorlage',
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
        ]);
        $admin->userFilterTemplates()->create([
            'name' => 'Terminlisten-Vorlage',
            'filter_type' => UserFilterTypes::PROJECT_EVENT_LIST_FILTER->value,
        ]);

        $names = collect($this->getJson(route('projects.tabs.bulk-edit', $project))->json('personalFilters'))
            ->pluck('name')
            ->all();

        $this->assertSame(['Terminlisten-Vorlage'], $names);
    }
}
