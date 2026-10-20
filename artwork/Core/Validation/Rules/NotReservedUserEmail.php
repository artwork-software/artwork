<?php

namespace Artwork\Core\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Sperrt die interne Domain artwork.local als Nutzer-E-Mail. Dort liegt der Platzhalter
 * „Gelöschte:r Nutzer:in“; früher wurde er über die E-Mail aufgelöst, sodass ein Konto mit dieser
 * Adresse alle Projekte, Termine usw. gelöschter Personen übernommen hätte.
 */
class NotReservedUserEmail implements ValidationRule
{
    public const RESERVED_DOMAIN = 'artwork.local';

    public static function isReserved(string $email): bool
    {
        $domain = strtolower(trim((string) substr(strrchr($email, '@') ?: '', 1)));

        return $domain === self::RESERVED_DOMAIN || str_ends_with($domain, '.' . self::RESERVED_DOMAIN);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::isReserved($value)) {
            $fail(__('This email address is reserved and cannot be used.'));
        }
    }
}
