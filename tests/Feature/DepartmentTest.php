<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins can view departments', function () {

    $admin_user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user->id);
        $user->departments()->attach($department->id);
    }

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $response = $this->get('/departments')
        ->assertInertia(fn(Assert $page) => $page
            ->component('DepartmentManagement')
            ->has('departments.data', 10)
            ->has('departments.data.0', fn(Assert $page) => $page
                ->hasAll(['id','name', 'users', 'logo_url'])
            )
            ->has('departments.data.0.users.0', fn(Assert $page) => $page
                ->hasAll('id', 'name', 'email', 'profile_photo_url')
            )
            ->where('departments.per_page', 10)
        );

    $response->assertStatus(200);
});
