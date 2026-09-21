<?php

namespace Tests\Unit\Policies;

use App\Policies\AccommodationPolicy;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Unterkünfte folgen dem Projektzugriff bzw. den CRM-Rechten
 * (Sicherheits-Audit 21.09.2026, Abschnitt C).
 */
final class AccommodationPolicyTest extends TestCase
{
    private AccommodationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(AccommodationPolicy::class);
    }

    private function grant(User $user, PermissionEnum $permission): User
    {
        Permission::findOrCreate($permission->value, 'web');
        $user->givePermissionTo($permission->value);

        return $user;
    }

    #[Test]
    public function user_without_project_or_crm_access_is_denied_everything(): void
    {
        $user = User::factory()->create();
        $accommodation = Accommodation::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $accommodation));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $accommodation));
        $this->assertFalse($this->policy->delete($user, $accommodation));
        $this->assertFalse($this->policy->restore($user, $accommodation));
        $this->assertFalse($this->policy->forceDelete($user, $accommodation));
    }

    #[Test]
    public function project_reader_may_view_but_not_manage(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => false]);
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $accommodation));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $accommodation));
        $this->assertFalse($this->policy->delete($user, $accommodation));
    }

    #[Test]
    public function project_writer_may_manage(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => true]);
        $accommodation = Accommodation::factory()->create();

        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $accommodation));
        $this->assertTrue($this->policy->delete($user, $accommodation));
        $this->assertTrue($this->policy->restore($user, $accommodation));
        $this->assertTrue($this->policy->forceDelete($user, $accommodation));
    }

    #[Test]
    public function crm_permissions_grant_access(): void
    {
        $viewer = $this->grant(User::factory()->create(), PermissionEnum::CRM_VIEW);
        $accommodation = Accommodation::factory()->create();
        $this->assertTrue($this->policy->viewAny($viewer));
        $this->assertTrue($this->policy->view($viewer, $accommodation));
        $this->assertFalse($this->policy->update($viewer, $accommodation));

        $manager = $this->grant(User::factory()->create(), PermissionEnum::CRM_MANAGER);
        $this->assertTrue($this->policy->create($manager));
        $this->assertTrue($this->policy->update($manager, $accommodation));
        $this->assertTrue($this->policy->delete($manager, $accommodation));
    }

    #[Test]
    public function unsaved_accommodation_cannot_be_viewed(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => true]);

        $this->assertFalse($this->policy->view($user, new Accommodation()));
        $this->assertFalse($this->policy->update($user, new Accommodation()));
    }
}
