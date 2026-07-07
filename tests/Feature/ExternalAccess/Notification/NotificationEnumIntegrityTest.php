<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationEnumIntegrityTest extends TestCase
{
    #[Test]
    public function every_notification_enum_case_resolves_seeding_match_methods(): void
    {
        // groupType/title/description are match() without default arm and are called by the
        // settings seeder for every case — a missing arm would crash seeding.
        foreach (NotificationEnum::cases() as $case) {
            $this->assertNotEmpty($case->groupType());
            $this->assertNotEmpty($case->title());
            $this->assertNotEmpty($case->description());
        }
    }

    #[Test]
    public function external_cases_resolve_to_their_notification_classes(): void
    {
        $this->assertSame(
            \Artwork\Modules\ExternalAccess\Notifications\ExternalCrmSubmissionNotification::class,
            NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->notificationClass(),
        );
        $this->assertSame(
            \Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification::class,
            NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->notificationClass(),
        );
    }

    #[Test]
    public function external_access_group_has_title_and_description(): void
    {
        $this->assertSame('External access', NotificationGroupEnum::EXTERNAL_ACCESS->title());
        $this->assertNotEmpty(NotificationGroupEnum::EXTERNAL_ACCESS->description());
    }

    #[Test]
    public function external_enum_cases_map_to_external_access_group(): void
    {
        $this->assertSame('EXTERNAL_ACCESS', NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->groupType());
        $this->assertSame('EXTERNAL_ACCESS', NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->groupType());
    }
}
