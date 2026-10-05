<?php

namespace Artwork\Modules\ExternalAccess\Console\Commands;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationSender;
use Illuminate\Console\Command;

/**
 * Erinnert die Einladenden wenige Tage vor Ablauf eines Tab- oder CRM-Zugangs (Einstellung
 * expiry_reminder_days, 0 = aus). Jeder Scope/Zugang wird genau einmal erinnert
 * (expiry_reminder_sent_at / crm_expiry_reminder_sent_at); eine Verlängerung setzt den Marker zurück.
 */
class SendExternalAccessExpiryRemindersCommand extends Command
{
    protected $signature = 'artwork:external-access:expiry-reminders';

    protected $description = 'Notify inviters a few days before an external tab or CRM access expires';

    public function handle(
        ExternalAccessSettingsResolver $settingsResolver,
        ExternalNotificationSender $notificationSender,
    ): int {
        if (!$settingsResolver->isEnabled()) {
            $this->info('External access is disabled; nothing to do.');

            return self::SUCCESS;
        }

        $days = $settingsResolver->expiryReminderDays();
        if ($days <= 0) {
            $this->info('Expiry reminders are disabled (0 days).');

            return self::SUCCESS;
        }

        $threshold = now()->addDays($days);
        $sent = 0;

        ExternalAccessScope::query()
            ->whereNull('expiry_reminder_sent_at')
            ->where('valid_to', '>', now())
            ->where('valid_to', '<=', $threshold)
            ->whereHas('externalAccess', fn ($q) => $q->whereNull('revoked_at'))
            ->with(['externalAccess.crmContact', 'externalAccess.invitedBy', 'project', 'projectTab'])
            ->orderBy('id')
            ->chunkById(100, function ($scopes) use ($notificationSender, &$sent): void {
                foreach ($scopes as $scope) {
                    $notificationSender->notifyScopeExpiring($scope);
                    $scope->forceFill(['expiry_reminder_sent_at' => now()])->save();
                    $sent++;
                }
            });

        ExternalAccess::query()
            ->whereNull('revoked_at')
            ->whereNull('crm_expiry_reminder_sent_at')
            ->whereNotNull('crm_access_expires_at')
            ->where('crm_access_expires_at', '>', now())
            ->where('crm_access_expires_at', '<=', $threshold)
            ->with(['crmContact', 'invitedBy'])
            ->orderBy('id')
            ->chunkById(100, function ($accesses) use ($notificationSender, &$sent): void {
                foreach ($accesses as $access) {
                    $notificationSender->notifyCrmAccessExpiring($access);
                    $access->forceFill(['crm_expiry_reminder_sent_at' => now()])->save();
                    $sent++;
                }
            });

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}
