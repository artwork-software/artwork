<?php

namespace Artwork\Modules\ExternalAccess\Notifications;

use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail an die externe Person, nachdem ihr abgesendeter Tab intern bestätigt oder zur Überarbeitung
 * zurückgegeben wurde. Externe Identitäten haben nur den Mail-Kanal.
 */
class ExternalTabReviewResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ExternalAccessScope $scope,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(GeneralSettings::class);
        $config = app(Repository::class);
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : $config->get('mail.fallback_page_title');
        $systemMail = $config->get('mail.system_mail');

        $status = $this->scope->submission_status;
        $subject = $status === ExternalTabSubmissionStatus::CONFIRMED
            ? __('Your data has been confirmed')
            : __('Your data has been returned for revision');

        return (new MailMessage())
            ->from(
                $settings->business_email !== '' ? $settings->business_email : $systemMail,
                $pageTitle,
            )
            ->subject($subject)
            ->markdown('emails.external-tab-review-result', [
                'pageTitle' => $pageTitle,
                'subject' => $subject,
                'status' => $status->value,
                'reviewer' => $this->scope->reviewedBy?->full_name ?? $pageTitle,
                'tabName' => $this->scope->projectTab?->name,
                'projectName' => $this->scope->project?->name,
                'comment' => $this->scope->review_comment,
                'externalLoginUrl' => route('external.login.form'),
            ]);
    }
}
