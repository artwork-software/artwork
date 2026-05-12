<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectStoreTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_project(): void
    {
        $this->post(route('projects.store'), [
            'name' => 'Foo',
            'isGroup' => false,
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_project(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.store'), [
            'name' => 'My New Project',
            'isGroup' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'My New Project']);
    }

    #[Test]
    public function user_without_permission_cannot_store_project(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('projects.store'), [
            'name' => 'No Perm',
            'isGroup' => false,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', ['name' => 'No Perm']);
    }

    #[Test]
    public function user_with_add_edit_own_project_can_store_project(): void
    {
        $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);

        $response = $this->post(route('projects.store'), [
            'name' => 'Own Project',
            'isGroup' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Own Project']);
    }

    #[Test]
    public function store_validates_required_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.store'), [
            'isGroup' => false,
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function store_validates_required_isgroup(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.store'), [
            'name' => 'No isGroup',
        ]);

        $response->assertSessionHasErrors('isGroup');
    }

    #[Test]
    public function store_rejects_invalid_state(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.store'), [
            'name' => 'Bad state',
            'isGroup' => false,
            'state' => 999999,
        ]);

        $response->assertSessionHasErrors('state');
    }

    #[Test]
    public function admin_can_create_group_project_with_marked_as_done_flag(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.store'), [
            'name' => 'Done Group',
            'isGroup' => true,
            'marked_as_done' => true,
        ]);

        $response->assertRedirect();
        $project = Project::query()->where('name', 'Done Group')->first();
        $this->assertNotNull($project);
        $this->assertTrue((bool) $project->is_group);
        $this->assertTrue((bool) $project->marked_as_done);
    }

    #[Test]
    public function admin_can_view_create_project_page(): void
    {
        $this->actingAsAdmin();
        $this->get(route('projects.create'))->assertOk();
    }
}
