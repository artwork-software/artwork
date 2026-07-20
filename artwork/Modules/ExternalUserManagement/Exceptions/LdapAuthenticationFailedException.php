<?php

namespace Artwork\Modules\ExternalUserManagement\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn ein Nutzer im LDAP-Verzeichnis existiert, der Bind mit dem
 * angegebenen Passwort aber fehlschlägt. Signalisiert dem Login: der Nutzer
 * gehört zum IdP – es darf kein lokaler Passwort-Fallback greifen.
 */
class LdapAuthenticationFailedException extends RuntimeException
{
}
