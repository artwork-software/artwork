<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserProjectManagementSetting;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserProjectManagementSettingServiceTest extends TestCase
{
    private UserProjectManagementSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserProjectManagementSettingService::class);
    }

    #[Test]
    public function get_defaults_returns_expected_shape(): void
    {
        $defaults = $this->service->getDefaults();

        $this->assertArrayHasKey('sort_by', $defaults);
        $this->assertArrayHasKey('project_filters', $defaults);
        $this->assertArrayHasKey('project_state_ids', $defaults);
        $this->assertNull($defaults['sort_by']);
        $this->assertTrue($defaults['project_filters']['showProjects']);
    }

    #[Test]
    public function get_from_user_returns_null_when_none(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->getFromUser($user));
    }

    #[Test]
    public function update_or_create_if_necessary_creates_when_missing(): void
    {
        $user = User::factory()->create();

        $setting = $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'name']);

        $this->assertInstanceOf(UserProjectManagementSetting::class, $setting);
        $this->assertTrue($setting->exists);
        $this->assertSame(['sort_by' => 'name'], $setting->settings);
    }

    #[Test]
    public function update_or_create_if_necessary_updates_existing(): void
    {
        $user = User::factory()->create();
        $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'old']);

        $setting = $this->service->updateOrCreateIfNecessary($user, ['sort_by' => 'new']);

        $this->assertSame(['sort_by' => 'new'], $setting->fresh()->settings);
        $this->assertSame(1, UserProjectManagementSetting::where('user_id', $user->id)->count());
    }

    #[Test]
    public function delete_all_removes_all_settings(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->service->updateOrCreateIfNecessary($user1, ['k' => 'v']);
        $this->service->updateOrCreateIfNecessary($user2, ['k' => 'v']);

        $this->service->deleteAll();

        $this->assertSame(0, UserProjectManagementSetting::count());
    }
}
