<?php

namespace Artwork\Modules\ExternalAccess\Exceptions;

use RuntimeException;

/**
 * Base for all external-access invitation errors. The HTTP layer renders these as 422
 * responses with the (translated) message.
 */
abstract class ExternalAccessException extends RuntimeException
{
}
