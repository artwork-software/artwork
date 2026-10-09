<?php

namespace Tests\Unit\Modules\WorkTime\Repositories;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkTimeBookingRepositoryTest extends TestCase
{
    #[Test]
    public function balance_updates_from_stale_user_models_do_not_overwrite_each_other(): void
    {
        $user = User::factory()->create(['work_time_balance' => 100]);
        // nächtlicher Lauf und manuelle Buchung halten je ein eigenes, unterschiedlich altes Model
        $nightlyRun = User::query()->findOrFail($user->id);
        $manualBooking = User::query()->findOrFail($user->id);
        $repository = app(WorkTimeBookingRepository::class);

        $repository->updateUserBalance($nightlyRun, 30);
        $repository->updateUserBalance($manualBooking, -10);

        $this->assertSame(120, (int) $user->fresh()->work_time_balance);
        $this->assertSame(130, (int) $nightlyRun->work_time_balance);
    }
}
