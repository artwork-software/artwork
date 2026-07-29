<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Facades\DB;

class ExternalUserSyncService
{
    public function __construct(
        private readonly LdapService $ldapService,
        private readonly ExternalUserService $externalUserService,
        private readonly ExternalUserGroupMappingService $groupMappingService,
        private readonly MailService $mailService
    ) {
    }

    /**
     * Synchronize a single LDAP source.
     *
     * @param callable|null $log Optional callback receiving progress lines (level, message).
     * @return array{total:int, synced:int, skipped:int}
     */
    public function syncSource(ExternalUserSource $source, ?callable $log = null): array
    {
        $notify = static function (string $level, string $message) use ($log): void {
            if ($log !== null) {
                $log($level, $message);
            }
        };

        $ldapUsers = $this->ldapService->fetchUsers($source);

        if ($ldapUsers->isEmpty()) {
            $notify('warn', "No users found for source: {$source->name}");
            return ['total' => 0, 'synced' => 0, 'skipped' => 0];
        }

        $total = $ldapUsers->count();
        $synced = 0;
        $skipped = 0;
        $notify('info', "Found {$total} users in LDAP");

        foreach ($ldapUsers as $ldapUser) {
            if (empty($ldapUser['identifier'])) {
                $skipped++;
                $notify('warn', 'Skipping user without identifier (dn: ' .
                    ($ldapUser['meta_data']['distinguished_name'] ?? 'unknown') . ')');
                continue;
            }

            try {
                $this->syncUser($source, $ldapUser);
                $synced++;
            } catch (\InvalidArgumentException $e) {
                // Erwartete Exception: User ohne E-Mail wird übersprungen
                $skipped++;
                $notify('warn', "Skipping user {$ldapUser['identifier']}: {$e->getMessage()}");
            } catch (\Throwable $e) {
                // Ein fehlgeschlagener User darf nicht den restlichen Lauf abbrechen
                // (sonst stoppt z. B. ein einzelner DB-/Mail-Fehler den 30-Minuten-Sync komplett)
                report($e);
                $skipped++;
                $notify('error', "Failed to sync user {$ldapUser['identifier']}");
            }
        }

        $notify('info', "Successfully synced source: {$source->name}");

        return ['total' => $total, 'synced' => $synced, 'skipped' => $skipped];
    }

    private function syncUser(ExternalUserSource $source, array $ldapUser): void
    {
        DB::transaction(function () use ($source, $ldapUser): void {
            $identifier = $ldapUser['identifier'];
            $groups = $ldapUser['groups'] ?? [];

            $user = $this->externalUserService->findOrCreateUser($source, $ldapUser, $identifier);

            if (!$user->ad_managed) {
                return;
            }

            $externalUser = $this->externalUserService->findOrCreateExternalUser(
                $source,
                $identifier,
                $ldapUser,
                $user
            );

            $this->externalUserService->syncUserGroups($source, $user, $groups, $this->groupMappingService);

            // Beim ersten Import einmalig eine Willkommens-Mail senden (Anmeldung mit den
            // Verzeichnis-Zugangsdaten – kein Passwort-Reset, der Account ist IdP-gebunden).
            // Das Flag wird atomar mit dem Import gesetzt; der Versand erfolgt erst nach
            // erfolgreichem Commit, damit die Mail bei einem Rollback nicht rausgeht.
            if ($externalUser->import_notification_sent_at === null && !empty($user->email)) {
                $externalUser->forceFill(['import_notification_sent_at' => now()])->save();

                DB::afterCommit(function () use ($externalUser, $user): void {
                    try {
                        $this->mailService->sendExternalUserImported($user, $externalUser);
                    } catch (\Throwable $e) {
                        // Mail-Fehler (z. B. SMTP down) darf den Sync nicht abbrechen und die
                        // Willkommens-Mail nicht dauerhaft verlieren: Flag zurücksetzen,
                        // damit der nächste Lauf den Versand erneut versucht
                        report($e);
                        $externalUser->forceFill(['import_notification_sent_at' => null])->save();
                    }
                });
            }
        });
    }
}
