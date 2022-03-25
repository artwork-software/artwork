<?php

use App\Mail\InvitationCreated;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\Assert;
use function Pest\Faker\faker;

it('aborts invalid tokens', function () {

    //Admin User
    $user = User::factory()->create();

    $user->assignRole('admin');

    Invitation::factory()->create(['user_id' => $user->id,'email' => 'user@example.com']);

    $password = "TesterTest_123?";

    $this->post('/users/invitations/accept', [
        'email' => 'user@example.com',
        'token' => 'invalidToken12345678',
        'password' => $password,
        'password_confirmation' => $password
    ])->assertForbidden();

});

it('aborts missing parameters', function () {

    $this->post('/users/invitations/accept', [
        'token' => 'invalid',
    ])->assertInvalid();

    $this->post('/users/invitations/accept', [
        'password' => faker()->password,
    ])->assertInvalid();
});

it('aborts weak passwords', function () {

    //create Invitation
    User::factory()->create();

    $invitation = Invitation::factory()->create();

    $this->post('/users/invitations/accept', [
        'token' => $invitation->token,
        'password' => 'weakpassword'
    ])->assertInvalid();
});

test('users can accept the invitation', function () {

    //create Invitation
    $user = User::factory()->create();

    $validPlainToken = 'validToken0123456789';

    $invitation = Invitation::factory()->create(['user_id' => $user->id, 'name' => 'testName', 'email' => 'user@example.com', 'token' => Hash::make($validPlainToken)]);

    $password = "TesterTest_123?";

    $this->post('/users/invitations/accept', [
        'email' => 'user@example.com',
        'token' => $validPlainToken,
        'password' => $password,
        'password_confirmation' => $password,
        'phone_number' => '123456789123',
        'position' => 'Chef',
        'business' => 'DTH',
        'description' => 'Ich bin Chef'
    ]);

    $this->assertDatabaseHas('users', [
        'name' => $invitation->name,
        'email' => $invitation->email,
    ]);

    $user = User::where('email', 'user@example.com')->first();

    $this->assertTrue(Hash::check($password,$user->password));

    $this->assertDeleted($invitation);

    $this->assertAuthenticated();
});

test('accept invitation renders', function () {

    User::factory()->create();

    $invitation = Invitation::factory()->create();

    $response = $this->get("/users/invitations/accept?token=$invitation->token?email=$invitation->email")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Invitations/Accept')
            ->has('token')
            ->has('email'));

    $response->assertStatus(200);
});

test('invitations requests are validated', function () {

    $user = User::factory()->create();

    $user->assignRole('admin');

    $this->actingAs($user);

    $this->post('/users/invitations', [
        'name' => null
    ])->assertInvalid();

});

test('admins can invite users', function () {

    Mail::fake();

    $admin_user = User::factory()->create();

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $response = $this->post('/users/invitations', [
        'name' => 'Test',
        'email' => 'user@example.de',
    ]);

    Mail::assertSent(InvitationCreated::class);

    $this->assertDatabaseHas('invitations', [
        "name" => "Test",
        "email" => "user@example.de",
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
        'name' => 'Test',
        'email' => 'user@example.de',
    ]);

    $this->assertDatabaseMissing('invitations', [
        "name" => "Test",
        "email" => "user@example.de",
        "token" => "test",
    ]);

    Mail::assertNothingSent();
    $response->assertStatus(403);
});

test('admins can view invitations', function () {

    $admin_user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        Invitation::factory()->create(['user_id' => $admin_user->id]);
    }

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $response = $this->get('/users/invitations')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Invitations/Invitations')
            ->has('invitations.data', 10)
            ->has('invitations.data.0', fn(Assert $page) => $page
                ->hasAll(['id','name', 'email'])
            )
            ->where('invitations.per_page', 10)
        );

    $response->assertStatus(200);
});

test('admins and can update invitations', function () {

    $admin_user = User::factory()->create();

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $invitation = Invitation::factory()->create(['user_id' => $admin_user->id]);

    $response = $this->patch("/users/invitations/{$invitation->id}", [
        'name' => 'Test',
        'email' => 'user@example.de',
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('invitations', [
        "name" => "Test",
        "email" => "user@example.de",
    ]);
});

test('admins can edit invitations', function () {

    $admin_user = User::factory()->create();

    $invitation = Invitation::factory()->create(['user_id' => $admin_user->id]);

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $response = $this->get("/users/invitations/{$invitation->id}/edit")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Invitations/InvitationEdit')
            ->has('invitation', fn(Assert $invitation) => $invitation
                ->hasAll(['id','name','email'])
            )
        );

    $response->assertStatus(200);

});

test('admins can delete invitations', function () {

    $admin_user = User::factory()->create();

    $admin_user->assignRole('admin');

    $this->actingAs($admin_user);

    $invitation = Invitation::factory()->create(['user_id' => $admin_user->id]);

    $this->delete("/users/invitations/{$invitation->id}")->assertStatus(302);

    $this->assertDatabaseMissing('invitations', [
        "name" => $invitation->name,
        "email" => $invitation->email,
        "token" => $invitation->token,
    ]);

});
