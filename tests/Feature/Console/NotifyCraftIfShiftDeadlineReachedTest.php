<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hinweis „offener Schichtbedarf“ am Stichtag des Gewerks: auch für Schichten ohne Termin (heute der
 * Normalfall, Schicht hängt am Projekt) – an Projektleitung und Planer*innen des Gewerks.
 */
final class NotifyCraftIfShiftDeadlineReachedTest extends FeatureTestCase
{
    #[Test]
    public function open_shifts_without_an_event_are_reported_to_managers_and_craft_planners(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $craft = Craft::factory()->create(['notify_days' => 2, 'assignable_by_all' => false]);
        $project = Project::factory()->create(['name' => 'Sommerfest']);
        [$manager, $planner, $worker] = User::factory()->count(3)->create()->all();
        foreach ([$manager, $planner, $worker] as $user) {
            app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        }
        $project->users()->attach($manager->id, ['is_manager' => true]);
        $craft->craftShiftPlaner()->attach($planner->id);
        $worker->assignedCrafts()->attach($craft->id);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'event_id' => null,
            'project_id' => $project->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        // Bedarf: 2 Personen, niemand eingeplant
        $shift->shiftsQualifications()->create([
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'value' => 2,
        ]);

        $this->artisan('artwork:notify-craft-if-shift-deadline-reached')->assertSuccessful();

        foreach ([$manager, $planner] as $recipient) {
            $data = $recipient->notifications()->sole()->data;
            $this->assertSame(NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND->value, $data['type']);
            $this->assertSame($project->id, $data['projectId']);
            $this->assertStringContainsString('Sommerfest', $data['title']);
        }
        $this->assertSame(0, $worker->notifications()->count());
    }
}
