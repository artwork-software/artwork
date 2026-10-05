<?php

namespace Tests\Unit\Policies;

use App\Policies\IndividualTimeSeriesPolicy;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Serien-Policy spiegelt die Regel des Einzel-Endpunkts (IndividualTimeController::store):
 * eigene Zeiten immer, fremde Nutzer*innen nur mit "can manage availability",
 * Freelancer/Dienstleister zusätzlich mit "can manage workers"/"can manage external workers".
 */
final class IndividualTimeSeriesPolicyTest extends TestCase
{
    private IndividualTimeSeriesPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(IndividualTimeSeriesPolicy::class);
    }

    #[Test]
    public function users_may_create_series_for_themselves_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue($this->policy->createForSubjects($user, [['type' => 'user', 'id' => $user->id]]));
        $this->assertFalse($this->policy->createForSubjects($user, [['type' => 'user', 'id' => $other->id]]));
        $this->assertFalse($this->policy->createForSubjects($user, [
            ['type' => 'user', 'id' => $user->id],
            ['type' => 'user', 'id' => $other->id],
        ]));
        $this->assertFalse($this->policy->createForSubjects($user, []));
    }

    #[Test]
    public function availability_management_allows_foreign_users_and_workers(): void
    {
        $user = $this->userWith(PermissionEnum::AVAILABILITY_MANAGEMENT);
        $other = User::factory()->create();
        $freelancer = Freelancer::factory()->create();

        $this->assertTrue($this->policy->createForSubjects($user, [
            ['type' => 'user', 'id' => $other->id],
            ['type' => 'freelancer', 'id' => $freelancer->id],
        ]));
    }

    #[Test]
    public function worker_managers_may_manage_workers_but_not_foreign_users(): void
    {
        $user = $this->userWith(PermissionEnum::EXTERNAL_MANAGER);
        $other = User::factory()->create();
        $freelancer = Freelancer::factory()->create();

        $this->assertTrue($this->policy->createForSubjects($user, [['type' => 'freelancer', 'id' => $freelancer->id]]));
        $this->assertFalse($this->policy->createForSubjects($user, [['type' => 'user', 'id' => $other->id]]));
    }

    #[Test]
    public function unknown_subject_types_are_rejected(): void
    {
        $user = $this->userWith(PermissionEnum::AVAILABILITY_MANAGEMENT);

        $this->assertFalse($this->policy->createForSubjects($user, [['type' => 'room', 'id' => 1]]));
    }

    private function userWith(PermissionEnum $permission): User
    {
        Permission::findOrCreate($permission->value, 'web');
        $user = User::factory()->create();
        $user->givePermissionTo($permission->value);

        return $user;
    }
}
