<?php

namespace Artwork\Modules\Task\Notifications;

use Artwork\Core\Notifications\BaseNotification;

/**
 * Mail-Aufbau zentral in BaseNotification::toMail(); die Klasse bleibt als
 * Typ-Kennung (Spalte notifications.type) erhalten.
 */
class TaskNotification extends BaseNotification
{
}
