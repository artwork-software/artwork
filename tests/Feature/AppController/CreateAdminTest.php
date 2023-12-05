<?php

namespace Tests\Feature\AppController;

use App\Enums\RoleNameEnum;
use App\Models\GeneralSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{


    public function testAbortsInvalidTokens()
    {
        Role::firstOrCreate(['name' => RoleNameEnum::ARTWORK_ADMIN->value]);

        $this->post('/setup', [
            'first_name' => 'Benjamin',
            'last_name' => 'Button',
            'email' => 'admin@example.com',
            'password' => 'TesterTest_123?',
            'phone_number' => '123123',
            'position' => "IT Administrator",
            'business' => 'DTH',
            'description' => 'Administrator',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
        ]);

        /** @var User $user */
        $user = User::where('email', 'admin@example.com',)->first();

        $this->assertTrue($user->refresh()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value));
        $this->assertTrue(app(GeneralSettings::class)->setup_finished);
    }
}
