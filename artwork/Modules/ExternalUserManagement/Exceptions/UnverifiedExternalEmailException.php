<?php

namespace Artwork\Modules\ExternalUserManagement\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn ein IdP ein neues Konto ohne verifizierte E-Mail anlegen würde und die
 * Quelle den E-Mail-Adressen des IdP nicht ausdrücklich vertraut.
 */
class UnverifiedExternalEmailException extends RuntimeException
{
}
