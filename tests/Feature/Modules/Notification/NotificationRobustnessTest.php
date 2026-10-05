<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Scheduling\Models\Scheduling;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Erzeugungsseite und Dialog-Daten der Benachrichtigungen: keine Nutzerdaten im Payload, kein
 * Zustand zwischen Empfänger*innen, keine Selbstbenachrichtigung, Dashboard = Benachrichtigungsseite,
 * IDs aus der URL werden autorisiert.
 */
final class NotificationRobustnessTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::swap(new ChannelManager($this->app));
    }

    private function recipient(): User
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    private function sendScheduled(): void
    {
        app(SchedulingService::class)->sendNotification(
            app(NotificationService::class),
            app(ProjectTabService::class)
        );
    }

    #[Test]
    public function the_creator_is_stored_without_contact_or_account_data(): void
    {
        $actor = User::factory()->create(['email' => 'actor@example.org', 'phone_number' => '0123']);
        $this->actingAs($actor);
        $recipient = $this->recipient();

        $service = app(NotificationService::class);
        $service->setTitle('Test');
        $service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
        $service->setNotificationTo($recipient);
        $service->createNotification();

        $createdBy = (array) $recipient->notifications()->sole()->data['created_by'];
        $this->assertSame(['id', 'first_name', 'last_name', 'profile_photo_url'], array_keys($createdBy));
        $this->assertSame($actor->id, $createdBy['id']);
    }

    #[Test]
    public function a_scheduled_change_is_not_reported_to_the_person_who_made_it(): void
    {
        $actor = $this->recipient();
        $project = Project::factory()->create();
        Scheduling::create([
            'user_id' => $actor->id, 'type' => 'PROJECT_CHANGES', 'model' => 'Project',
            'model_id' => $project->id, 'count' => 1, 'created_by_id' => $actor->id,
        ]);

        $this->sendScheduled();

        $this->assertSame(0, $actor->notifications()->count());
        $this->assertDatabaseCount('schedulings', 0);
    }

    #[Test]
    public function scheduled_notifications_do_not_inherit_data_from_the_previous_one(): void
    {
        $eventRecipient = $this->recipient();
        $projectRecipient = $this->recipient();
        $event = Event::factory()->create();
        $project = Project::factory()->create();
        Scheduling::create([
            'user_id' => $eventRecipient->id, 'type' => 'EVENT_CHANGES', 'model' => 'Event',
            'model_id' => $event->id, 'count' => 1,
        ]);
        Scheduling::create([
            'user_id' => $projectRecipient->id, 'type' => 'PROJECT_CHANGES', 'model' => 'Project',
            'model_id' => $project->id, 'count' => 1,
        ]);

        $this->sendScheduled();

        $eventData = $eventRecipient->notifications()->sole()->data;
        $this->assertSame($event->id, $eventData['eventId']);
        // vorher: Termin, Raumzeile und Termin-Verlauf von A in der Projektmeldung von B
        $projectData = $projectRecipient->notifications()->sole()->data;
        $this->assertNull($projectData['eventId']);
        $this->assertSame([], (array) $projectData['description']);
        $this->assertSame('project', $projectData['historyType']);
        $this->assertSame($project->id, $projectData['modelId']);
    }

    #[Test]
    public function absence_changes_go_to_the_person_and_the_planners_of_their_crafts(): void
    {
        $craft = Craft::factory()->create();
        $worker = $this->recipient();
        $colleague = $this->recipient();
        $planner = $this->recipient();
        $worker->assignedCrafts()->attach($craft->id);
        $colleague->assignedCrafts()->attach($craft->id);
        $craft->craftShiftPlaner()->attach($planner->id);
        Scheduling::create([
            'user_id' => $worker->id, 'type' => 'VACATION_CHANGES', 'model' => 'USER_VACATIONS',
            'model_id' => $worker->id, 'count' => 1,
        ]);

        $this->sendScheduled();

        $this->assertSame(1, $worker->notifications()->count());
        $this->assertSame(1, $planner->notifications()->count());
        // vorher gingen sie an die Arbeitskräfte der Gewerke, die die Person selbst plant
        $this->assertSame(0, $colleague->notifications()->count());
    }

    #[Test]
    public function the_dashboard_gets_the_same_dialog_data_as_the_notification_page(): void
    {
        $admin = $this->actingAsAdmin();
        $event = Event::factory()->create();
        $event->comments()->create(['user_id' => $admin->id, 'comment' => 'Rückfrage', 'is_admin_comment' => true]);

        foreach (['dashboard', 'notifications.index'] as $routeName) {
            foreach (['openEditEvent', 'openDeclineEvent'] as $flag) {
                $this->get(route($routeName, [$flag => true, 'eventId' => $event->id]))
                    ->assertOk()
                    ->assertInertia(fn ($page) => $page
                        ->where('event.id', $event->id)
                        ->where('event.canEdit', true)
                        ->where('event.comments.0.comment', 'Rückfrage')
                        ->where('wantedSplit', $event->room_id)
                        ->etc());
            }
        }
    }

    #[Test]
    public function history_from_the_url_is_only_shown_with_access_to_the_record(): void
    {
        $project = Project::factory()->create();
        $changeService = app(ChangeService::class);
        $changeService->saveFromBuilder(
            $changeService->createBuilder()
                ->setType('project')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Project created')
        );
        $query = ['showHistory' => true, 'historyType' => 'project', 'modelId' => $project->id];

        $this->actingAs(User::factory()->create());
        $this->get(route('notifications.index', $query))
            ->assertInertia(fn ($page) => $page->where('historyObjects', [])->etc());

        $this->actingAsAdmin();
        $this->get(route('notifications.index', $query))
            ->assertInertia(fn ($page) => $page->has('historyObjects', 1)->etc());
    }

    #[Test]
    public function absence_history_of_other_people_needs_planning_rights(): void
    {
        $other = User::factory()->create();
        $query = ['showHistory' => true, 'historyType' => 'vacations', 'modelId' => $other->id];
        $viewer = User::factory()->create();
        $this->actingAs($viewer);

        $this->get(route('notifications.index', $query))->assertOk();
        $this->assertFalse(
            (fn () => $this->mayViewAbsences($viewer, $other->id))
                ->call(app(\Artwork\Modules\Notification\Services\NotificationDialogDataService::class))
        );
        $this->assertTrue(
            (fn () => $this->mayViewAbsences($viewer, $viewer->id))
                ->call(app(\Artwork\Modules\Notification\Services\NotificationDialogDataService::class))
        );
    }

    #[Test]
    public function the_dialogs_survive_an_event_whose_creator_was_deleted(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $event->forceFill(['user_id' => null])->save();

        $this->get(route('notifications.index', ['openDeclineEvent' => true, 'eventId' => $event->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('event.created_by', null)->etc());
    }
}
