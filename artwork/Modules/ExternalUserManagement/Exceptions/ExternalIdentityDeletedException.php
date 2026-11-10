<?php

namespace Artwork\Modules\ExternalUserManagement\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn eine externe Identität neu angelegt würde, deren artwork-Konto
 * bereits gelöscht wurde. Wiederherstellen geht nur über eine neue Einladung.
 */
class ExternalIdentityDeletedException extends RuntimeException
{
}
