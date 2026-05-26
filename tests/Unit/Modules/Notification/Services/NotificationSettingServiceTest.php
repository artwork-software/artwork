<?php

namespace Tests\Unit\Modules\Notification\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationSettingServiceTest extends TestCase
{
    private NotificationSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationSettingService::class);
    }

    #[Test]
    public function create_persists_a_notification_setting(): void
    {
        $user = User::factory()->create();
        $type = NotificationEnum::NOTIFICATION_ROOM_REQUEST;

        $setting = $this->service->create([
            'user_id' => $user->id,
            'group_type' => $type->groupType(),
            'type' => $type->value,
            'title' => $type->title(),
            'description' => $type->description(),
            'enabled_email' => true,
            'enabled_push' => true,
        ]);

        $this->assertInstanceOf(NotificationSetting::class, $setting);
        $this->assertTrue($setting->exists);
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'type' => $type->value,
        ]);
    }

    #[Test]
    public function get_notification_enum_cases_returns_all_cases(): void
    {
        $cases = $this->service->getNotificationEnumCases();

        $this->assertNotEmpty($cases);
        $this->assertContainsOnlyInstancesOf(NotificationEnum::class, $cases);
    }

    #[Test]
    public function get_enabled_of_user_returns_collection(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getEnabledOfUser($user->id);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
