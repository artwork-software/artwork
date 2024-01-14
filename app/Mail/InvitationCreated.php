<?php

namespace App\Mail;

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
        return $this->from("einladung@test.de", $this->user->first_name)
            ->replyTo($this->user->email)
            ->subject("Einladung für das artwork")
            ->markdown('emails.invitations', ['token' => $this->token]);
    }
}
