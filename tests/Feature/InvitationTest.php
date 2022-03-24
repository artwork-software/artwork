<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    $invitation = Invitation::factory()->create(['user_id' => $user->id, 'email' => 'user@example.com', 'token' => Hash::make($validPlainToken)]);

    $password = "TesterTest_123?";

    $this->post('/users/invitations/accept', [
        'email' => 'user@example.com',
        'token' => $validPlainToken,
        'password' => $password,
        'password_confirmation' => $password
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
