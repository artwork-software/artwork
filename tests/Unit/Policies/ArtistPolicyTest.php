<?php

namespace Tests\Unit\Policies;

use App\Policies\ArtistPolicy;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Künstler*innen folgen dem Projektzugriff: Lesen/Export mit Sicht-, Verwalten mit Schreibrecht.
 */
final class ArtistPolicyTest extends TestCase
{
    private ArtistPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ArtistPolicy::class);
    }

    private function artist(): Artist
    {
        return Artist::query()->create(['name' => 'Test']);
    }

    #[Test]
    public function user_without_any_project_access_is_denied_everything(): void
    {
        $user = User::factory()->create();
        $artist = $this->artist();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $artist));
        $this->assertFalse($this->policy->export($user));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $artist));
        $this->assertFalse($this->policy->delete($user, $artist));
        $this->assertFalse($this->policy->restore($user, $artist));
        $this->assertFalse($this->policy->forceDelete($user, $artist));
    }

    #[Test]
    public function project_reader_may_read_and_export_but_not_manage(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => false]);
        $artist = $this->artist();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $artist));
        $this->assertTrue($this->policy->export($user));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $artist));
        $this->assertFalse($this->policy->delete($user, $artist));
    }

    #[Test]
    public function project_writer_may_manage(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => true]);
        $artist = $this->artist();

        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $artist));
        $this->assertTrue($this->policy->delete($user, $artist));
        $this->assertTrue($this->policy->restore($user, $artist));
        $this->assertTrue($this->policy->forceDelete($user, $artist));
    }

    #[Test]
    public function project_manager_and_creator_may_manage(): void
    {
        $manager = User::factory()->create();
        Project::factory()->create()->users()->attach($manager, ['can_write' => false, 'is_manager' => true]);
        $this->assertTrue($this->policy->create($manager));

        $creator = User::factory()->create();
        Project::factory()->create(['user_id' => $creator->id]);
        $this->assertTrue($this->policy->create($creator));
    }

    #[Test]
    public function global_project_permissions_grant_access(): void
    {
        $viewer = User::factory()->create();
        Permission::findOrCreate(PermissionEnum::PROJECT_VIEW->value, 'web');
        $viewer->givePermissionTo(PermissionEnum::PROJECT_VIEW->value);
        $this->assertTrue($this->policy->viewAny($viewer));
        $this->assertFalse($this->policy->create($viewer));

        $writer = User::factory()->create();
        Permission::findOrCreate(PermissionEnum::WRITE_PROJECTS->value, 'web');
        $writer->givePermissionTo(PermissionEnum::WRITE_PROJECTS->value);
        $this->assertTrue($this->policy->viewAny($writer));
        $this->assertTrue($this->policy->create($writer));
    }

    #[Test]
    public function unsaved_artist_cannot_be_viewed(): void
    {
        $user = User::factory()->create();
        Project::factory()->create()->users()->attach($user, ['can_write' => true]);

        $this->assertFalse($this->policy->view($user, new Artist()));
        $this->assertFalse($this->policy->update($user, new Artist()));
    }
}
