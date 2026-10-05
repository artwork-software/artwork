<?php

namespace Artwork\Modules\Notification\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationSummary extends Mailable
{
    use Queueable;

    use SerializesModels;

    public array $notifications;

    public string $user;

    public string $page_title;

    public string $systemEmail;

    public string $fallbackPageTitle;

    public string $language;

    public function __construct(
        array $notifications,
        string $user,
        string $page_title,
        string $systemEmail,
        string $fallbackPageTitle,
        ?string $language = null
    ) {
        $this->notifications = $notifications;
        $this->user = $user;
        $this->page_title = $page_title;
        $this->systemEmail = $systemEmail;
        $this->fallbackPageTitle = $fallbackPageTitle;
        $this->language = $language ?: (string) config('app.locale');
    }

    public function envelope(): Envelope
    {
        $pageTitle = $this->page_title !== '' ? $this->page_title : $this->fallbackPageTitle;

        return new Envelope(
            from: $this->systemEmail,
            subject: __('There is news in :app', ['app' => $pageTitle], $this->language)
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifications',
            with: [
                'notifications' => $this->notifications,
                'user' => $this->user,
                'page_title' => $this->page_title,
                'language' => $this->language,
            ]
        );
    }
}
