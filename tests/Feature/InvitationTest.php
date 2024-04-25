<?php

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Invitation\Mail\InvitationCreated;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('accept invitation renders', function () {

    User::factory()->create();

    $invitation = Invitation::factory()->create();

    $response = $this->get("/users/invitations/accept?token=$invitation->token?email=$invitation->email")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Users/Accept')
            ->has('token')
            ->has('email'));

    $response->assertStatus(200);
});

test('invitations requests are validated', function () {

    $user = User::factory()->create();

    $user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

    $this->actingAs($user);

    $this->post('/users/invitations', [
        'name' => null
    ]);

    $this->assertDatabaseCount('invitations', 0);

});

test('admins can invite users', function () {

    Mail::fake();

    $admin_user = User::factory()->create();

    $department = Department::factory()->create();

    $admin_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

    $this->actingAs($admin_user);

    $response = $this->post('/users/invitations', [
        'user_emails' => ['user@example.de', 'user2@example.de'],
        'permissions' => ['invite users', 'view users'],
        'departments' => [$department],
        'roles' => []
    ]);

    Mail::assertSent(InvitationCreated::class, function ($mail) use ($admin_user) {
        $mail->build();
        return $mail->subject = 'Einladung für artwork';
    });

    $this->assertDatabaseHas('invitations', [
        "email" => "user@example.de",
    ]);

    $this->assertDatabaseHas('invitations', [
        "email" => "user2@example.de",
    ]);

    $invitation = Invitation::where('email', 'user@example.de')->first();

    $this->assertDatabaseHas('department_invitation', [
        "invitation_id" => $invitation->id,
        "department_id" => $department->id
    ]);

    $created_invitation = Invitation::first();

    expect($created_invitation->token)->toHaveLength(60);
    expect($created_invitation->token)->toMatch('/^\$2y\$/');
    $response->assertValid();
    $response->assertStatus(302);
    $response->assertSessionHasNoErrors();
});

test('non admins cannot invite users', function () {

    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/users/invitations', [
        'users' => ['email'=> 'user@example.de'],
        'permissions' => ['invite users', 'view users'],
    ]);

    $this->assertDatabaseMissing('invitations', [
        "email" => "user@example.de",
        "token" => "test",
    ]);

    Mail::assertNothingSent();
    $response->assertStatus(403);
});

test('admins can view invitations', function () {

    $admin_user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        Invitation::factory()->create();
    }

    $admin_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

    $this->actingAs($admin_user);

    $response = $this->get('/users/invitations')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Users/Invitations')
            ->has('invitations.data', 10)
            ->has('invitations.data.0', fn(Assert $page) => $page
                ->hasAll(['id','name', 'email','created_at'])
            )
            ->where('invitations.per_page', 10)
        );

    $response->assertStatus(200);
});

test('admins and can update invitations', function () {

    Mail::fake();

    $admin_user = User::factory()->create();

    $admin_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

    $this->actingAs($admin_user);

    $invitation = Invitation::factory()->create();

    $response = $this->patch("/users/invitations/{$invitation->id}", [
        'email' => 'user@example.de',
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('invitations', [
        "email" => "user@example.de",
    ]);
});

test('admins can edit invitations', function () {

    $admin_user = User::factory()->create();

    $invitation = Invitation::factory()->create();

    $admin_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($admin_user);

    $response = $this->get("/users/invitations/{$invitation->id}/edit")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Users/InvitationEdit')
            ->has('invitation', fn(Assert $invitation) => $invitation
                ->hasAll(['id', 'email'])
            )
        );

    $response->assertStatus(200);

});

test('admins can delete invitations', function () {

    $admin_user = User::factory()->create();

    $admin_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);

    $this->actingAs($admin_user);

    $invitation = Invitation::factory()->create();

    $this->delete("/users/invitations/{$invitation->id}")->assertStatus(302);

    $this->assertDatabaseMissing('invitations', [
        "email" => $invitation->email,
        "token" => $invitation->token,
    ]);

});
