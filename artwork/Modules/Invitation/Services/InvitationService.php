<?php

namespace Artwork\Modules\Invitation\Services;

use Artwork\Core\Mail\MailService;
use Artwork\Core\Str\StrService;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Invitation\Repositories\InvitationRepository;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Collection as SupportCollection;
use Psr\Log\LoggerInterface;

class InvitationService
{
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
                        'roles' => $roles
                    ]
                );
            } else {
                $invitation = $this->create(
                    [
                        'email' => $email,
                        'token' => $token['hash'],
                        'permissions' => $permissions,
                        'roles' => $roles
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
