<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * user/vacation/{vacation}/delete: der Parameter hieß {userVacations}, der Controller erwartet $vacation.
 * Ohne Model-Binding bekam er eine leere Abwesenheit und scheiterte mit einem 500er.
 */
final class UserVacationRouteBindingTest extends FeatureTestCase
{
    #[Test]
    public function deleting_a_user_vacation_binds_the_entry(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-12-01',
            'full_day' => true,
            'is_series' => false,
        ]);

        $this->delete(route('user.vacation.delete', $vacation))->assertRedirect();

        self::assertNull($vacation->fresh());
    }
}
