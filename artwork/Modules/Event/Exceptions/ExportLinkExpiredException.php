<?php

namespace Artwork\Modules\Event\Exceptions;

use RuntimeException;

/**
 * Der Download-Link eines Termin-/Kalender-Exports gilt nur kurz und einmal (Reload oder Zurück im
 * Download-Tab landen hier).
 */
class ExportLinkExpiredException extends RuntimeException
{
}
