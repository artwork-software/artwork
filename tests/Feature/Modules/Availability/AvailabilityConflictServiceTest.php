<?php

namespace Tests\Feature\Modules\Availability;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welle-1-Regression (B5): Die Bedingungen in checkAvailabilityConflictsOnDay
 * waren invertiert (if (!$user) statt if ($user)) — User bekamen nie eine
 * Konflikt-Notification, der Freelancer-Pfad crashte mit TypeError.
 */
final class AvailabilityConflictServiceTest extends FeatureTestCase
{
    private const DAY = '2026-06-15';

    #[Test]
    public function user_with_conflicting_committed_shift_gets_conflict_and_notification(): void
    {
        $user = User::factory()->create();
        $this->assignToCommittedShift($user);
        $user->availabilities()->create([
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        // Anderer eingeloggter User, damit die Notification nicht am
        // "nicht an sich selbst"-Guard scheitert.
        $this->actingAsAdmin();

        app(AvailabilityConflictService::class)->checkAvailabilityConflictsOnDay(
            self::DAY,
            app(NotificationService::class),
            user: $user,
        );

        $this->assertSame(1, AvailabilitiesConflict::query()->count());
        Notification::assertSentTo($user, ShiftNotification::class);
    }

    #[Test]
    public function freelancer_conflict_check_does_not_crash_and_creates_conflict(): void
    {
        $freelancer = Freelancer::factory()->create();
        $this->assignToCommittedShift($freelancer);
        $freelancer->availabilities()->create([
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $this->actingAsAdmin();

        app(AvailabilityConflictService::class)->checkAvailabilityConflictsOnDay(
            self::DAY,
            app(NotificationService::class),
            freelancer: $freelancer,
        );

        $this->assertSame(1, AvailabilitiesConflict::query()->count());
    }

    /**
     * Der Konflikt-Hinweis muss die Person nennen, die die Zuweisung vorgenommen
     * hat — nicht die Person, die den Schichtplan festgeschrieben/genehmigt hat
     * (Bulk-Festschreiben und Workflow-Genehmigung setzen committing_user_id auf
     * Unbeteiligte).
     */
    #[Test]
    public function conflict_names_the_assigner_not_the_committer(): void
    {
        $user = User::factory()->create();
        $assigner = User::factory()->create();

        // Zuweisung als $assigner: der creating-Hook auf ShiftWorker persistiert
        // Auth::id() als assigned_by_user_id.
        $this->actingAs($assigner);
        $shift = $this->assignToCommittedShift($user);

        $user->availabilities()->create([
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $this->actingAsAdmin();

        app(AvailabilityConflictService::class)->checkAvailabilityConflictsOnDay(
            self::DAY,
            app(NotificationService::class),
            user: $user,
        );

        $conflict = AvailabilitiesConflict::query()->sole();
        $this->assertSame($assigner->full_name, $conflict->user_name);
        $this->assertNotSame($shift->committedBy()->first()->full_name, $conflict->user_name);
    }

    /**
     * Alt-Zuweisungen ohne assigned_by_user_id fallen auf den bisherigen
     * Festschreibenden zurück.
     */
    #[Test]
    public function conflict_falls_back_to_committer_for_legacy_assignments(): void
    {
        $user = User::factory()->create();
        // Zuweisung ohne eingeloggten User → assigned_by_user_id bleibt null.
        $shift = $this->assignToCommittedShift($user);

        $user->availabilities()->create([
            'date' => self::DAY,
            'full_day' => false,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $this->actingAsAdmin();

        app(AvailabilityConflictService::class)->checkAvailabilityConflictsOnDay(
            self::DAY,
            app(NotificationService::class),
            user: $user,
        );

        $conflict = AvailabilitiesConflict::query()->sole();
        $this->assertSame($shift->committedBy()->first()->full_name, $conflict->user_name);
    }

    /**
     * Festgeschriebene Schicht 13:00–17:00 am Tag — liegt komplett außerhalb
     * der (Teil-)Verfügbarkeit 10:00–12:00 und erzeugt damit einen Konflikt.
     */
    private function assignToCommittedShift(User|Freelancer $worker): Shift
    {
        $committedBy = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'event_start_day' => self::DAY,
            'event_end_day' => self::DAY,
            'start' => '13:00',
            'end' => '17:00',
            'is_committed' => true,
            'committing_user_id' => $committedBy->id,
        ]);

        $qualification = ShiftQualification::factory()->create();
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        return $shift;
    }
}
