<?php


namespace Tests\Feature;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{



    public function testPasswordCanBeUpdated()
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password1234',
        ]);

        $this->assertTrue(Hash::check('new-password1234', $user->fresh()->password));
    }

    public function testCurrentPasswordMustBeCorrect()
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password1234',
        ]);

        $response->assertSessionHasErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function testNewPasswordsMustMatch()
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password1234',
        ]);

        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }
}
