<?php

namespace Tests\Feature\Notifications;

use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Die Meldungen-Seite listet ihre Gruppen hart als NotificationSectionComponent-Blöcke.
 * Eine Gruppe, die gezählt, aber nicht gerendert wird, ist für Nutzer*innen unsichtbar
 * (so geschehen mit EXTERNAL_ACCESS) — deshalb muss jede Enum-Gruppe einen Block haben.
 */
final class NotificationGroupSectionIntegrityTest extends TestCase
{
    #[Test]
    public function every_notification_group_has_a_section_on_the_notifications_page(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Notifications/Show.vue'));
        $this->assertNotFalse($vue);

        foreach (NotificationGroupEnum::cases() as $group) {
            $this->assertStringContainsString(
                'group-type="' . $group->value . '"',
                $vue,
                sprintf('Gruppe %s hat keinen NotificationSectionComponent-Block in Notifications/Show.vue', $group->value),
            );
            $this->assertStringContainsString(
                "notificationCounts['" . $group->value . "']",
                $vue,
                sprintf('Gruppe %s liest keine Zähler in Notifications/Show.vue', $group->value),
            );
        }
    }
}
