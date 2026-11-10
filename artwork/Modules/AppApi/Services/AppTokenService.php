<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\User\Models\User;
use Laravel\Passport\Token;

/**
 * Gerätetokens der App (Scope "app"). Sie laufen 90 Tage statt Passports Standard von einem Jahr
 * (oauth_access_tokens.expires_at, vom ExtendedTokenValidator geprüft) und werden bei Passwortwechsel
 * und -reset widerrufen; sonst bliebe ein gestohlenes Gerät trotz neuem Passwort angemeldet.
 * Maschinen-Tokens der Schnittstellen (andere Scopes) bleiben unberührt.
 */
class AppTokenService
{
    public const SCOPE = 'app';

    public const LIFETIME_DAYS = 90;

    public function issue(User $user, string $deviceName): string
    {
        $result = $user->createToken($deviceName, [self::SCOPE]);

        $result->token?->forceFill(['expires_at' => now()->addDays(self::LIFETIME_DAYS)])->save();

        return $result->accessToken;
    }

    public function revokeAll(User $user): int
    {
        $revoked = 0;

        $user->tokens()
            ->where('revoked', false)
            ->get()
            ->filter(static fn (Token $token): bool => in_array(self::SCOPE, (array) $token->scopes, true))
            ->each(static function (Token $token) use (&$revoked): void {
                $token->revoke();
                $revoked++;
            });

        return $revoked;
    }
}
