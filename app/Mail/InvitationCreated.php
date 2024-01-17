<?php

namespace App\Mail;

use App\Models\GeneralSettings;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Invitation $invitation, public Authenticatable $user, public string $token)
    {
    }

    public function build(): InvitationCreated
    {
        $settings = app(GeneralSettings::class);
        return $this
            ->from(
                $settings->business_email !== '' ? $settings->business_email : 'noreply@artwork.de',
                'Artwork'
            )
            ->replyTo($this->user->email)
            ->subject("Einladung für das artwork")
            ->markdown('emails.invitations', ['token' => $this->token]);
    }
}
