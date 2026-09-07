<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 4: Die Personalverwaltung liefert je Person das Flag work_time_pattern_missing
 * (Schichtarbeitende ohne heute gültiges Arbeitszeitmuster) – mit konstanter Query-Zahl.
 */
final class UserIndexWorkTimePatternFlagTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pattern(User $user, string $validFrom, ?string $validUntil = null): void
    {
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function usersFromIndex(): array
    {
        $users = [];
        $this->get(route('users'))->assertInertia(
            function (AssertableInertia $page) use (&$users): void {
                $page->component('Users/Index');
                $users = $page->toArray()['props']['users'];
            }
        );

        return collect($users)->keyBy('id')->all();
    }

    #[Test]
    public function the_flag_marks_shift_workers_without_a_pattern_valid_today(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $withPattern = User::factory()->create(['can_work_shifts' => true]);
        $expiredPattern = User::factory()->create(['can_work_shifts' => true]);
        $withoutPattern = User::factory()->create(['can_work_shifts' => true]);
        $noShiftWorker = User::factory()->create(['can_work_shifts' => false]);
        $this->pattern($withPattern, '2026-01-01');
        $this->pattern($expiredPattern, '2026-01-01', '2026-06-30');

        $users = $this->usersFromIndex();

        $this->assertFalse($users[$withPattern->id]['work_time_pattern_missing']);
        $this->assertTrue($users[$expiredPattern->id]['work_time_pattern_missing']);
        $this->assertTrue($users[$withoutPattern->id]['work_time_pattern_missing']);
        $this->assertFalse($users[$noShiftWorker->id]['work_time_pattern_missing']);
        $this->assertTrue($users[$withPattern->id]['can_work_shifts']);
        $this->assertFalse($users[$noShiftWorker->id]['can_work_shifts']);
    }

    #[Test]
    public function the_flag_does_not_add_a_query_per_listed_person(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        User::factory()->count(2)->create(['can_work_shifts' => true]);

        $few = $this->countQueries(fn () => $this->get(route('users'))->assertOk());

        User::factory()->count(8)->create(['can_work_shifts' => true]);
        $many = $this->countQueries(fn () => $this->get(route('users'))->assertOk());

        $this->assertLessThanOrEqual(
            $few + 1,
            $many,
            "Query-Zahl wächst mit der Personenzahl (N+1): {$few} vs. {$many}"
        );
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
