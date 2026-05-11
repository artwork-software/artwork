<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CategoryControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_settings_page(): void
    {
        $this->get(route('project.settings'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_project_settings_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('project.settings'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Settings/ProjectSettings')
                ->has('categories')
                ->has('genres')
                ->has('sectors')
                ->has('currencies')
                ->has('states')
        );
    }

    #[Test]
    public function guest_cannot_store_category(): void
    {
        $this->post(route('categories.store'), ['name' => 'Foo', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('categories.store'), [
            'name' => 'Concerts',
            'color' => '#abcdef',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Concerts']);
    }

    #[Test]
    public function admin_can_update_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::query()->forceCreate([
            'name' => 'Theatre',
            'color' => '#111',
        ]);

        $response = $this->patch(route('categories.update', $category), [
            'name' => 'Theatre Updated',
            'color' => '#222',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Theatre Updated',
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_store_category(): void
    {
        // CategoryPolicy::create requires PROJECT_SETTINGS_UPDATE permission.
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('categories.store'), [
            'name' => 'NoPerm',
            'color' => '#fff',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function user_with_project_settings_update_permission_can_store_category(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $response = $this->post(route('categories.store'), [
            'name' => 'PermOk',
            'color' => '#fff',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'PermOk']);
    }
}
