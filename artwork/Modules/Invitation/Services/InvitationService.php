<?php

namespace Artwork\Modules\Invitation\Services;

use Artwork\Core\Mail\MailService;
use Artwork\Core\Str\StrService;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Invitation\Repositories\InvitationRepository;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Psr\Log\LoggerInterface;

class InvitationService
{
    /** Gültigkeitsdauer eines Einladungstokens in Tagen. */
    public const EXPIRES_AFTER_DAYS = 7;

    public function __construct(
        private readonly InvitationRepository $invitationRepository,
        private readonly LoggerInterface $logger,
        private readonly UserService $userService,
        private readonly HashManager $hashManager,
        private readonly StrService $strService,
        private readonly MailService $mailService
    ) {
    }

    public function findByEmail(string $email): ?Invitation
    {
        return $this->invitationRepository->findByEmail($email);
    }

    public function findByToken(string $token): ?Invitation
    {
        return $this->invitationRepository->findByToken($token);
    }

    public function create(array $attributes): Invitation
    {
        /** @var Invitation $invitation */
        $this->invitationRepository->save(
            ($invitation = $this->invitationRepository->getNewModelInstance())
                ->fill($attributes)
        );

        return $invitation;
    }

    public function createOrUpdate(
        array $permissions,
        array $roles,
        array $userEmails,
        SupportCollection $departmentIds
    ): void {
        $admin_user = $this->userService->getAuthUser();
        $permissions = $this->filterGrantablePermissions($permissions, $admin_user);

        foreach ($userEmails as $email) {
            do {
                $tokenPlain = $this->strService->random(20);
                $hashedToken = $this->hashManager->make($tokenPlain);
            } while ($this->findByToken($hashedToken));

            $token = [
                'plain' => $tokenPlain,
                'hash' => $hashedToken
            ];

            if (($invitation = $this->findByEmail($email))) {
                $this->logger->info(
                    sprintf(
                        'Attempt to recreate invitation for email: "%s" - Token (Hashed): "%s"',
                        $email,
                        $token['hash']
                    )
                );
                $this->update(
                    $invitation,
                    [
                        'token' => $token['hash'],
                        'permissions' => $permissions,
                        'roles' => $roles,
                        'expires_at' => $this->newExpiresAt(),
                    ]
                );
            } else {
                $invitation = $this->create(
                    [
                        'email' => $email,
                        'token' => $token['hash'],
                        'permissions' => $permissions,
                        'roles' => $roles,
                        'expires_at' => $this->newExpiresAt(),
                    ]
                );
            }

            $this->syncDepartments($invitation, $departmentIds);

            $this->mailService->sendInvitationCreated(
                $email,
                $invitation,
                $admin_user,
                $token['plain']
            );

            $this->logger->info(
                sprintf(
                    'Sent invitation (re-)created for email: %s - Token (Hashed): %s',
                    $email,
                    $token['hash']
                )
            );
        }
    }

    /**
     * Einladungen dürfen nur Rechte vergeben, die die einladende Person selbst besitzt (direkt oder
     * über Rollen) — sonst ließe sich über eine Zweitadresse jedes Recht erschleichen. Admins vergeben
     * uneingeschränkt. Wird serverseitig gefiltert, nicht nur validiert.
     *
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    public function filterGrantablePermissions(array $permissions, ?User $inviter): array
    {
        if ($inviter === null) {
            return [];
        }

        if ($inviter->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return array_values(array_unique($permissions));
        }

        return array_values(array_unique(array_intersect($permissions, $inviter->allPermissions())));
    }

    public function newExpiresAt(): Carbon
    {
        return Carbon::now()->addDays(self::EXPIRES_AFTER_DAYS);
    }

    public function update(Invitation $invitation, array $attributes): Invitation
    {
        $this->invitationRepository->update(
            $invitation,
            $attributes
        );

        return $invitation;
    }

    public function syncDepartments(Invitation $invitation, SupportCollection $departmentIds): Invitation
    {
        $this->invitationRepository->syncDepartments($invitation, $departmentIds);

        return $invitation;
    }
}
