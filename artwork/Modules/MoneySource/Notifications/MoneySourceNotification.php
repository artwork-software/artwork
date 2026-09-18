<?php

namespace Artwork\Modules\MoneySource\Notifications;

use Artwork\Core\Notifications\BaseNotification;

/**
 * Mail-Aufbau zentral in BaseNotification::toMail(); die Klasse bleibt als
 * Typ-Kennung (Spalte notifications.type) erhalten.
 */
class MoneySourceNotification extends BaseNotification
{
}
