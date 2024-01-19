<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\UserCreateRequest;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
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

    /**
     * @throws AuthorizationException
     */
    public function toolSettingsIndex(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        return Inertia::render('Settings/ToolSettings');
    }

    /**
     * @throws AuthorizationException
     */
    public function updateToolImages(Request $request, GeneralSettings $settings): RedirectResponse
    {
        $this->authorize('updateToolImages', $settings);

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

    /**
     * @throws AuthorizationException
     */
    public function updateToolEmailSettings(Request $request, GeneralSettings $settings): RedirectResponse
    {
        $this->authorize('updateToolEmailSettings', $settings);

        $settings->business_name = $request->get('businessName') ?? '';
        $settings->impressum_link = $request->get('impressumLink') ?? '';
        $settings->privacy_link = $request->get('privacyLink') ?? '';
        $settings->email_footer = $request->get('emailFooter') ?? '';
        $settings->business_email = $request->get('businessEmail') ?? '';

        $settings->save();

        return Redirect::back()->with('success', 'Email Einstellungen angepasst');
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
}
