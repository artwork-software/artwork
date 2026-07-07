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
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;

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
                /** @var GeneralSettings $settings */
                $settings = $this->app->make(GeneralSettings::class);
                $config = $this->app->make(Repository::class);
                $fallbackSenderMail = $config->get('mail.system_mail');
                $pageTitle = $settings->page_title !== '' ?
                    $settings->page_title :
                    $config->get('mail.fallback_page_title');

                return (new MailMessage())
                    ->from(
                        $settings->business_email !== '' ? $settings->business_email : $fallbackSenderMail,
                        $pageTitle
                    )
                    ->subject('Passwort zurücksetzen')
                    ->markdown(
                        'emails.password_reset',
                        [
                            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
                            'page_title' => $settings->page_title !== '' ? $settings->page_title : 'Artwork',
                            'url' => sprintf(
                                '%s/reset-password/%s?email=%s',
                                $config->get('app.url'),
                                $token,
                                $notifiable->email
                            )
                        ]
                    );
            }
        );

        // Custom Login Response für Inertia.js - erzwingt Full-Page-Reload nach Login
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    $intended = redirect()->intended(RouteServiceProvider::HOME)->getTargetUrl();

                    // Für Inertia-Requests: Force Full-Page-Reload
                    if ($request->header('X-Inertia')) {
                        return response('', 409)
                            ->header('X-Inertia-Location', $intended);
                    }

                    return redirect()->intended(RouteServiceProvider::HOME);
                }
            };
        });
    }
}
