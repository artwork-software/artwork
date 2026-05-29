<?php

namespace Artwork\Modules\ExternalAccess\Notifications;

use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail-only notification for the external person after their submission was reviewed.
 * External identities have no database/push channel — only mail.
 */
class ExternalReviewResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ExternalPendingSubmission $submission,
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

        $approvedCount = $this->submission->fieldChanges()
            ->where('approval_status', FieldApprovalStatus::APPROVED)
            ->count();
        $rejectedCount = $this->submission->fieldChanges()
            ->where('approval_status', FieldApprovalStatus::REJECTED)
            ->count();

        $status = $this->submission->status;
        $subject = match ($status) {
            ExternalSubmissionStatus::APPROVED => __('Your changes have been accepted'),
            ExternalSubmissionStatus::REJECTED => __('Your changes have been declined'),
            ExternalSubmissionStatus::PARTIALLY_APPROVED => __('Your changes have been partially accepted'),
            default => __('Update on your submission'),
        };

        return (new MailMessage())
            ->from(
                $settings->business_email !== '' ? $settings->business_email : $systemMail,
                $pageTitle,
            )
            ->subject($subject)
            ->markdown('emails.external-review-result', [
                'pageTitle' => $pageTitle,
                'subject' => $subject,
                'status' => $status->value,
                'approvedCount' => $approvedCount,
                'rejectedCount' => $rejectedCount,
                'rejectionReason' => $this->submission->rejection_reason,
                'externalLoginUrl' => route('external.login.form'),
            ]);
    }
}
