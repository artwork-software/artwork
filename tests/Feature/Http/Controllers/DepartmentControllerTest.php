<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Department\Models\Department;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class DepartmentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_departments_index(): void
    {
        $this->get(route('departments'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_departments_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('departments'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store_department(): void
    {
        $this->post(route('departments.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_department(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('departments.store'), [
            'name' => 'Lighting',
            'svg_name' => 'lightSvg',
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', ['name' => 'Lighting']);
    }

    #[Test]
    public function admin_can_show_department(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $this->get(route('departments.show', $department))->assertOk();
    }

    #[Test]
    public function admin_can_view_edit_department(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $this->get(route('departments.profile', $department))->assertOk();
    }

    #[Test]
    public function admin_can_destroy_department(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $response = $this->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('departments'));
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    #[Test]
    public function admin_can_remove_all_members(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $response = $this->patch(route('departments.remove.members', $department));

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_search_departments(): void
    {
        $this->actingAsAdmin();
        Department::factory()->create(['name' => 'Findable']);

        $response = $this->get(route('departments.search', ['query' => 'Findable']));

        $response->assertOk();
    }
}
