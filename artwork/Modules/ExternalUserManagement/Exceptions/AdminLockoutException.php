<?php

namespace Artwork\Modules\ExternalUserManagement\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn eine Operation den letzten lokal authentifizierten
 * Admin-Account IdP-binden und damit die Instanz bei einem IdP-Ausfall
 * komplett aussperren würde.
 */
class AdminLockoutException extends RuntimeException
{
}
