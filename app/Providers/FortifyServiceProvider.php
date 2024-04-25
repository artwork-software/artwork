<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // @phpstan-ignore-next-line
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email . $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        ResetPassword::toMailUsing(
            function (User $notifiable, string $token) {
                $settings = app(GeneralSettings::class);
                return (new MailMessage())
                    ->from(
                        $settings->business_email !== '' ? $settings->business_email : 'noreply@artwork.software',
                        'Artwork'
                    )
                    ->subject('Passwort zurücksetzen')
                    ->markdown(
                        'emails.password_reset',
                        [
                            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
                            'url' => sprintf(
                                '%s/reset-password/%s?email=%s',
                                config('app.url'),
                                $token,
                                $notifiable->email
                            )
                        ]
                    );
            }
        );
    }
}
