<?php

namespace Artwork\Modules\ExternalUserManagement\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class ExternalUserImported extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public ExternalUser $externalUser,
    ) {
    }

    public function build(): ExternalUserImported
    {
        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);

        $fallbackSenderMail = Config::get('mail.system_mail');
        $senderAddress = $settings->business_email !== '' ? $settings->business_email : $fallbackSenderMail;
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : Config::get('mail.fallback_page_title');

        return $this
            ->from($senderAddress, $pageTitle)
            ->subject('Willkommen bei ' . $pageTitle)
            ->markdown(
                'emails.external_user_imported',
                [
                    'name' => trim($this->user->first_name . ' ' . $this->user->last_name),
                    'page_title' => $pageTitle,
                    'url' => rtrim((string) Config::get('app.url'), '/') . '/login',
                    'sender_email' => $senderAddress,
                ]
            );
    }

    public function failed(Throwable $exception): void
    {
        // Dauerhafte Ablehnung durch den Mailserver (SMTP 5xx, z. B. "550 Message rejected"):
        // Flag NICHT zurücksetzen, sonst versucht jeder Sync-Lauf den Versand erneut und
        // erzeugt eine Endlosschleife aus failed_jobs und Sentry-Meldungen. Nur bei
        // vorübergehenden Fehlern (SMTP down, 4xx) wird der Versand beim nächsten Lauf wiederholt.
        if (self::isPermanentTransportFailure($exception)) {
            Log::warning('[ExternalUserImported] Willkommens-Mail dauerhaft abgelehnt, kein erneuter Versuch', [
                'external_user_id' => $this->externalUser->getKey(),
                'user_id' => $this->user->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        ExternalUser::query()
            ->whereKey($this->externalUser->getKey())
            ->update(['import_notification_sent_at' => null]);
    }

    private static function isPermanentTransportFailure(Throwable $exception): bool
    {
        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof TransportExceptionInterface) {
                $code = (int) $e->getCode();

                return $code >= 500 && $code < 600;
            }
        }

        return false;
    }
}
