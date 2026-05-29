<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Core\Str\StrService;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Artwork\Modules\ExternalAccess\Notifications\ExternalInvitationNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalLoginLinkNotification;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessRepository;
use Artwork\Modules\ExternalAccess\Repositories\ExternalLoginTokenRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class ExternalLoginService
{
    public function __construct(
        private readonly ExternalAccessRepository $externalAccessRepository,
        private readonly ExternalLoginTokenRepository $externalLoginTokenRepository,
        private readonly StrService $strService,
    ) {
    }

    public function requestLoginLink(
        string $email,
        ?string $ip,
        ?string $userAgent,
        bool $isInvitation = false,
        ?User $invitedBy = null,
        ?Project $invitedFromProject = null,
    ): void {
        $external = $this->externalAccessRepository->findByEmail($email);

        if ($external === null || $external->revoked_at !== null || !$external->hasAnyActiveAccess()) {
            return;
        }

        $plainToken = $this->strService->random(64);
        $tokenHash = hash('sha256', $plainToken);
        $lifetimeMinutes = (int) config('external_access.login_token.lifetime_minutes', 15);

        ExternalLoginToken::query()->create([
            'external_access_id' => $external->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes($lifetimeMinutes),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $notification = $isInvitation
            ? new ExternalInvitationNotification($plainToken, $invitedBy, $invitedFromProject)
            : new ExternalLoginLinkNotification($plainToken);

        Notification::route('mail', $external->routeNotificationForMail())
            ->notify($notification);
    }

    public function redeemToken(string $plainToken): ?ExternalAccess
    {
        $tokenHash = hash('sha256', $plainToken);

        $external = DB::transaction(function () use ($tokenHash): ?ExternalAccess {
            $token = $this->externalLoginTokenRepository->findByHashWithLock($tokenHash);

            if ($token === null || !$token->isValid()) {
                return null;
            }

            $external = $token->externalAccess()->lockForUpdate()->first();

            if ($external === null || $external->revoked_at !== null || !$external->hasAnyActiveAccess()) {
                return null;
            }

            $token->forceFill(['used_at' => now()])->save();
            $external->forceFill(['last_login_at' => now()])->save();

            ExternalInvitation::query()
                ->where('external_access_id', $external->id)
                ->whereNull('first_redeemed_at')
                ->orderBy('id')
                ->limit(1)
                ->update(['first_redeemed_at' => now()]);

            return $external;
        });

        if ($external === null) {
            return null;
        }

        $guard = Auth::guard('external');
        if (!method_exists($guard, 'login')) {
            throw new RuntimeException('External guard does not support stateful login.');
        }
        $guard->login($external);
        Session::regenerate();

        return $external;
    }

    public function logout(): void
    {
        $guard = Auth::guard('external');
        if (method_exists($guard, 'logout')) {
            $guard->logout();
        }
        Session::invalidate();
        Session::regenerateToken();
    }
}
