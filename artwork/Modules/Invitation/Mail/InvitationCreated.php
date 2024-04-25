<?php

namespace Artwork\Modules\Invitation\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\User\Models\User;
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
            ->markdown(
                'emails.invitations',
                [
                    'token' => $this->token,
                    'super_user_email' => User::query()->find(1)?->email
                ]
            );
    }
}
