<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Providers\RouteServiceProvider;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Http\Requests\UserCreateRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use ZxcvbnPhp\Zxcvbn;

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

        return Redirect::back();
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

        return Redirect::back();
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

        return Redirect::back();
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

        return Redirect::back();
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

        return Redirect::back();
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

        return Redirect::back();
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

    public function createAdmin(
        UserCreateRequest $request,
        GeneralSettings $settings,
        StatefulGuard $guard
    ): Redirector|Application|RedirectResponse {
        /** @var User $user */
        $user = User::create($request->userData());

        foreach (NotificationEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);
        }

        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
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
