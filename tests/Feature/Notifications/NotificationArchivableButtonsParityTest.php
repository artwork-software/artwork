<?php

namespace Tests\Feature\Notifications;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Archivieren-Icon (Frontend) und erlaubtes Archivieren (Backend) dürfen nicht auseinanderlaufen –
 * vorher zeigte das Frontend das Icon bei Kalender-/Rückgabe-Buttons, das Backend lehnte still ab.
 */
final class NotificationArchivableButtonsParityTest extends TestCase
{
    #[Test]
    public function frontend_and_backend_list_the_same_archivable_buttons(): void
    {
        $source = (string) file_get_contents(
            resource_path('js/Layouts/Components/NotificationComponents/archivableButtons.js')
        );
        $this->assertSame(1, preg_match('/ARCHIVABLE_BUTTONS = \[(.*?)\]/s', $source, $match));
        preg_match_all("/'([A-Za-z_]+)'/", $match[1], $buttons);

        $this->assertEqualsCanonicalizing(DatabaseNotificationService::ARCHIVABLE_BUTTONS, $buttons[1]);
    }
}
