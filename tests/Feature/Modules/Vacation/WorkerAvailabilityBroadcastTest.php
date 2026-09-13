<?php

namespace Tests\Feature\Modules\Vacation;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Events\WorkerAvailabilityChanged;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Verfügbarkeits-CRUD muss den Dienstplan anderer Clients informieren
 * (Personenzeile wird per Echo nachgeladen).
 */
final class WorkerAvailabilityBroadcastTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([WorkerAvailabilityChanged::class]);
    }

    #[Test]
    public function storing_own_vacation_broadcasts_worker_change(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('user.vacation.add', $user), [
                'date' => '2026-08-03',
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => false,
            ])
            ->assertSuccessful();

        Event::assertDispatched(
            WorkerAvailabilityChanged::class,
            fn (WorkerAvailabilityChanged $e) => $e->workerId === $user->id && $e->workerType === 0
        );
    }

    #[Test]
    public function updating_availability_entry_broadcasts_worker_change(): void
    {
        $user = User::factory()->create();
        $availability = Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => $user->id,
            'date' => '2026-08-03',
            'full_day' => true,
            'is_series' => false,
            'series_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('update.availability.entry', $availability), [
                'date' => '2026-08-04',
                'type' => 'available',
                'full_day' => true,
                'is_series' => false,
            ])
            ->assertRedirect();

        Event::assertDispatched(
            WorkerAvailabilityChanged::class,
            fn (WorkerAvailabilityChanged $e) => $e->workerId === $user->id && $e->workerType === 0
        );
    }

    #[Test]
    public function deleting_vacation_broadcasts_worker_change(): void
    {
        $user = User::factory()->create();
        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-08-03',
            'is_series' => false,
            'series_id' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('delete.vacation', $vacation))
            ->assertRedirect();

        Event::assertDispatched(
            WorkerAvailabilityChanged::class,
            fn (WorkerAvailabilityChanged $e) => $e->workerId === $user->id && $e->workerType === 0
        );
    }

    #[Test]
    public function freelancer_morph_maps_to_worker_type_one(): void
    {
        $freelancer = Freelancer::factory()->create();

        $event = WorkerAvailabilityChanged::forMorph(Freelancer::class, $freelancer->id);

        $this->assertSame(1, $event->workerType);
        $this->assertSame(['workerId' => $freelancer->id, 'workerType' => 1], $event->broadcastWith());
        $this->assertSame('worker-availability.changed', $event->broadcastAs());
    }
}
