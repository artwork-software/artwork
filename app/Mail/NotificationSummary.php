<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class NotificationSummary extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $notifications;

    public function __construct(Collection $notifications)
    {
        $this->notifications = $notifications;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hier Betreff einfügen',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'hier.markdown.verwenden',
            with: [
                'notifications' => $this->notifications
            ]
        );
    }
}
