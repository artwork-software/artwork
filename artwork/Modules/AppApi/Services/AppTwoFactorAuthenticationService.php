<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\User\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Same checks as Fortify's TwoFactorLoginRequest, without the session-bound
 * challenge: the app sends the code together with the credentials.
 */
class AppTwoFactorAuthenticationService
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {
    }

    public function verify(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if (!$user->hasEnabledTwoFactorAuthentication()) {
            return true;
        }

        if ($code !== null) {
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if ($this->provider->verify($secret, $code)) {
                return true;
            }
        }

        if ($recoveryCode === null) {
            return false;
        }

        $matchedCode = collect($user->recoveryCodes())
            ->first(static fn (string $storedCode): bool => hash_equals($storedCode, $recoveryCode));

        if ($matchedCode === null) {
            return false;
        }

        $user->replaceRecoveryCode($matchedCode);

        return true;
    }
}
