<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 4: "h/Woche laut Vertrag" ist kein Eingabefeld mehr. updateUserTerms ignoriert
 * weekly_working_hours; UserShowResource liefert die Wochenstunden aus dem Muster.
 */
final class UserTermsUpdateTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function update_terms_ignores_weekly_working_hours_but_saves_the_remuneration(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $user = User::factory()->create(['weekly_working_hours' => 40, 'salary_per_hour' => 10]);

        $this->patch(route('user.update.terms', $user), [
            'weekly_working_hours' => 20,
            'salary_per_hour' => 13,
            'salary_description' => 'Zulage',
        ])->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertEquals(40, $fresh->weekly_working_hours); // unverändert
        $this->assertEquals(13, $fresh->salary_per_hour); // Spalte ist ganzzahlig
        $this->assertSame('Zulage', $fresh->salary_description);
    }

    #[Test]
    public function update_terms_requires_the_worker_management_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $user = User::factory()->create();

        $this->patch(route('user.update.terms', $user), ['salary_per_hour' => 1])->assertForbidden();
    }

    #[Test]
    public function show_resource_reports_weekly_hours_from_the_current_pattern_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));
        $user = User::factory()->create(['weekly_working_hours' => 40]);

        $this->assertNull((new UserShowResource($user))->toArray(request())['weekly_working_hours']);

        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '07:42',
            'tuesday' => '07:42',
            'wednesday' => '07:42',
            'thursday' => '07:42',
            'friday' => '07:42',
            'saturday' => null,
            'sunday' => null,
            'valid_from' => '2026-07-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(38.5, (new UserShowResource($user->fresh()))->toArray(request())['weekly_working_hours']);
    }
}
