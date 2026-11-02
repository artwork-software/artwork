<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

/**
 * Entzieht einem Konto alle bisherigen Zugänge: lokales Passwort, Browser-Sitzungen, Remember-Me und
 * sämtliche Passport-Tokens (App und Maschinen-Schlüssel). Wird genutzt, wenn ein lokales Konto an
 * eine LDAP/OIDC-Identität gebunden wird – ab dann gilt nur noch der IdP, und wer das Konto vorher
 * (etwa über eine vorab geänderte E-Mail-Adresse) besessen hat, verliert den Zugriff.
 */
class UserCredentialRevocationService
{
    public function revokeAll(User $user): void
    {
        $user->forceFill([
            'password' => Hash::make(Str::random(64)),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }

        $user->tokens()
            ->where('revoked', false)
            ->get()
            ->each(static fn (Token $token) => $token->revoke());
    }
}
