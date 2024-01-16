<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\UserCreateRequest;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use ZxcvbnPhp\Zxcvbn;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\ResponseFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Foundation\Application;

class AppController extends Controller
{
    use PasswordValidationRules;

    public function getPasswordScore(Request $request): int
    {
        return (new Zxcvbn())->passwordStrength($request->input('password'))['score'];
    }

    //@todo: fix phpcs error - refactor function name to toggleHints
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_hints(): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'toggle_hints' => !$user->toggle_hints
        ]);

        return Redirect::back()->with('success', 'Hilfe umgeschaltet');
    }

    //@todo: fix phpcs error - refactor function name to toggleCalendarSettingsProjectStatus
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_calendar_settings_project_status(): RedirectResponse
    {
        $user = Auth::user();

        $calendarSettings = $user->calendar_settings()->first();

        $user->calendar_settings()->update([
            'project_status' => !$calendarSettings->project_status
        ]);

        return Redirect::back()->with('success', 'Einstellung gespeichert');
    }

    //@todo: fix phpcs error - refactor function name to toggleCalendarSettingsOptions
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_calendar_settings_options(): RedirectResponse
    {
        $user = Auth::user();

        $calendarSettings = $user->calendar_settings()->first();

        $user->calendar_settings()->update([
            'options' => !$calendarSettings->options
        ]);

        return Redirect::back()->with('success', 'Einstellung gespeichert');
    }

    //@todo: fix phpcs error - refactor function name to toggleCalendarSettingsProjectManagement
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_calendar_settings_project_management(): RedirectResponse
    {
        $user = Auth::user();

        $calendarSettings = $user->calendar_settings()->first();

        $user->calendar_settings()->update([
            'project_management' => !$calendarSettings->project_management
        ]);

        return Redirect::back()->with('success', 'Einstellung gespeichert');
    }

    //@todo: fix phpcs error - refactor function name to toggleCalendarSettingsRepeatingEvents
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_calendar_settings_repeating_events(): RedirectResponse
    {
        $user = Auth::user();

        $calendarSettings = $user->calendar_settings()->first();

        $user->calendar_settings()->update([
            'repeating_events' => !$calendarSettings->repeating_events
        ]);

        return Redirect::back()->with('success', 'Einstellung gespeichert');
    }

    //@todo: fix phpcs error - refactor function name to toggleCalendarSettingsWorkShifts
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function toggle_calendar_settings_work_shifts(): RedirectResponse
    {
        $user = Auth::user();

        $calendarSettings = $user->calendar_settings()->first();

        $user->calendar_settings()->update([
            'work_shifts' => !$calendarSettings->work_shifts
        ]);

        return Redirect::back()->with('success', 'Einstellung gespeichert');
    }

    public function index(GeneralSettings $settings): RedirectResponse
    {
        //setup process finished
        return $settings->setup_finished ? Redirect::route('login') : Redirect::route('setup');
    }

    public function showSetupPage(GeneralSettings $settings): RedirectResponse|Response|ResponseFactory
    {
        //setup process finished
        return $settings->setup_finished ? Redirect::route('login') : inertia('Auth/Register');
    }

    public function updateTool(Request $request, GeneralSettings $settings): RedirectResponse
    {
        if (!Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            throw new MethodNotAllowedHttpException(
                ['update'],
                'Fehlende Berechtigung zum Ändern der Seiten Einstellungen'
            );
        }

        $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');

        if ($smallLogo) {
            $settings->small_logo_path = $smallLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($bigLogo) {
            $settings->big_logo_path = $bigLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($banner) {
            $settings->banner_path = $banner->storePublicly('banner', ['disk' => 'public']);
        }

        $settings->save();

        return Redirect::back()->with('success', 'Fotos hinzugefügt');
    }

    public function createAdmin(
        UserCreateRequest $request,
        GeneralSettings $settings,
        StatefulGuard $guard
    ): Redirector|Application|RedirectResponse {
        /** @var User $user */
        $user = User::create($request->userData());

        foreach (NotificationConstEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);
        }

        $user->assignRole(RoleNameEnum::ARTWORK_ADMIN->value);
        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $guard->login($user);

        $settings->setup_finished = true;
        $settings->company_name = $request['business'];
        $settings->save();

        return redirect(RouteServiceProvider::HOME);
    }

    public function updateEmailSettings(Request $request, GeneralSettings $settings): RedirectResponse
    {
        if (!Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            throw new MethodNotAllowedHttpException(['update'], 'Nur Admins können Email Einstellungen ändern');
        }

        if ($request->businessName != $settings->business_name) {
            $settings->business_name = $request->businessName;
        }

        if ($request->impressumLink != $settings->impressum_link) {
            $settings->impressum_link = $request->impressumLink;
        }

        if ($request->privacyLink != $settings->privacy_link) {
            $settings->privacy_link = $request->privacyLink;
        }

        if ($request->emailFooter != $settings->email_footer) {
            $settings->email_footer = $request->emailFooter;
        }

        $settings->save();

        return Redirect::back()->with('success', 'Email Einstellungen angepasst');
    }
}
