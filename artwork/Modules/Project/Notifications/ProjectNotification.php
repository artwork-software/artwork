<?php

namespace Artwork\Modules\Project\Notifications;

use Artwork\Core\Notifications\BaseNotification;

/**
 * Mail-Aufbau zentral in BaseNotification::toMail(); die Klasse bleibt als
 * Typ-Kennung (Spalte notifications.type) erhalten.
 */
class ProjectNotification extends BaseNotification
{
}
