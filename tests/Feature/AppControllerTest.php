<?php

namespace Tests\Feature;

use App\Http\Controllers\AppController;
use App\Providers\RouteServiceProvider;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppControllerTest extends TestCase
{
    use WithoutMiddleware;

    private AppController $appController;

    private GeneralSettings $generalSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appController = app(AppController::class);
        $this->generalSettings = app(GeneralSettings::class);
    }

    public function testIndex(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
    }

    public function testShowSetupPage(): void
    {
        $this->generalSettings->setup_finished = false;
        $this->generalSettings->save();

        $response = $this->get('/setup');

        $response->assertStatus(200);
    }

    public function testAbortsInvalidTokens()
    {
        Role::firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value]);

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

        $this->assertTrue($user->refresh()->hasRole(RoleEnum::ARTWORK_ADMIN->value));
        $this->assertTrue(app(GeneralSettings::class)->setup_finished);
    }
}
