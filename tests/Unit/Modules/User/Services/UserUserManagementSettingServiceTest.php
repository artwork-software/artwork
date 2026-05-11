<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserUserManagementSetting;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserUserManagementSettingServiceTest extends TestCase
{
    private UserUserManagementSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserUserManagementSettingService::class);
    }

    #[Test]
    public function get_defaults_returns_sort_by_null(): void
    {
        $this->assertSame(['sort_by' => null], $this->service->getDefaults());
    }

    #[Test]
    public function get_from_user_returns_null_initially(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->getFromUser($user));
    }

    #[Test]
    public function update_or_create_if_necessary_creates_when_missing(): void
    {
        $user = User::factory()->create();

        $setting = $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'first_name']);

        $this->assertInstanceOf(UserUserManagementSetting::class, $setting);
        $this->assertSame(['sort_by' => 'first_name'], $setting->settings);
    }

    #[Test]
    public function update_or_create_if_necessary_updates_existing(): void
    {
        $user = User::factory()->create();
        $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'a']);

        $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'b']);

        $fresh = UserUserManagementSetting::where('user_id', $user->id)->first();
        $this->assertSame(['sort_by' => 'b'], $fresh->settings);
        $this->assertSame(1, UserUserManagementSetting::where('user_id', $user->id)->count());
    }

    #[Test]
    public function delete_all_clears_settings(): void
    {
        $user = User::factory()->create();
        $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'a']);

        $this->service->deleteAll();

        $this->assertSame(0, UserUserManagementSetting::count());
    }
}
