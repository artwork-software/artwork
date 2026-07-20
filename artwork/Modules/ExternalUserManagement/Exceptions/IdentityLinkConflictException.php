<?php

namespace Artwork\Modules\ExternalUserManagement\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn eine externe Identität per E-Mail auf einen Account
 * matcht, der bereits an eine andere IdP-Identität gebunden ist.
 */
class IdentityLinkConflictException extends RuntimeException
{
}
