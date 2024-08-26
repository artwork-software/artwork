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

    public function __construct(
        array $notifications,
        string $user,
        string $page_title,
        string $systemEmail,
        string $fallbackPageTitle
    ) {
        $this->notifications = $notifications;
        $this->user = $user;
        $this->page_title = $page_title;
        $this->systemEmail = $systemEmail;
        $this->fallbackPageTitle = $fallbackPageTitle;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->systemEmail,
            subject: 'Es gibt Neuigkeiten in ' . (
                $this->page_title !== '' ? $this->page_title : $this->fallbackPageTitle
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifications',
            with: [
                'notifications' => $this->notifications,
                'user' => $this->user,
                'page_title' => $this->page_title
            ]
        );
    }
}
