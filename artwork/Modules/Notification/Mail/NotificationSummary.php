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

    public function __construct(array $notifications, string $user)
    {
        $this->notifications = $notifications;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Es gibt Neuigkeiten in deinem artwork!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifications',
            with: [
                'notifications' => $this->notifications,
                'user' => $this->user
            ]
        );
    }
}
