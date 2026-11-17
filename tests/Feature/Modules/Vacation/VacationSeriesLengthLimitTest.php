<?php

namespace Tests\Feature\Modules\Vacation;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Serien von Abwesenheiten/Verfügbarkeiten laufen höchstens fünf Jahre (vorher unbegrenzt, z. B. bis 2100). */
final class VacationSeriesLengthLimitTest extends FeatureTestCase
{
    #[Test]
    public function series_longer_than_five_years_is_rejected_without_creating_entries(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();

        $this->post(route('freelancer.vacation.add', $freelancer), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'full_day' => true,
            'is_series' => true,
            'series_repeat' => 'daily',
            'series_repeat_until' => '2100-01-01',
        ])->assertSessionHasErrors('series_repeat_until');

        self::assertSame(0, Vacation::where('vacationer_type', Freelancer::class)->where('vacationer_id', $freelancer->id)->count());
    }

    #[Test]
    public function weekly_series_of_exactly_five_years_is_allowed(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('user.vacation.add', $user), [
            'date' => '2026-12-01',
            'type' => 'vacation',
            'full_day' => true,
            'is_series' => true,
            'series_repeat' => 'weekly',
            'series_repeat_until' => '2031-12-01',
        ])->assertSessionHasNoErrors();

        self::assertGreaterThan(250, Vacation::where('vacationer_type', User::class)->where('vacationer_id', $user->id)->count());
    }
}
