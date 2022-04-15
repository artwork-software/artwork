<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZxcvbnPhp\Zxcvbn;


class AppController extends Controller
{
    use PasswordValidationRules;

    protected StatefulGuard $guard;

    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    public function get_password_feedback(): int
    {
        if(strlen(request('password'))) {
            $zxcvbn = new Zxcvbn();
            return $zxcvbn->passwordStrength(request('password'))['score'];
        } else {
            return 0;
        }
    }

    /**
     */
    public function validate_email(Request $request): \Illuminate\Http\JsonResponse
    {
        if(Auth::user()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = null;
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user_id)],
        ]);

        return response()->json($validator->errors());
    }

    public function toggle_hints(): \Illuminate\Http\RedirectResponse
    {

        $user = Auth::user();

        $user->update([
           'toggle_hints' => !$user->toggle_hints
        ]);

        return Redirect::back()->with('success', 'Hilfe umgeschaltet');
    }

    public function index(GeneralSettings $settings): \Illuminate\Http\RedirectResponse
    {

        //setup process finished
        if($settings->setup_finished) {
            return Redirect::route('login');
        } else {
            return Redirect::route('setup');
        }

    }

    public function setup_company(GeneralSettings $settings): \Illuminate\Http\RedirectResponse|\Inertia\Response|\Inertia\ResponseFactory
    {

        //setup process finished
        if($settings->setup_finished) {
            return Redirect::route('login');
        } else {
            return inertia('Auth/Register');
        }

    }

    public function update_tool(Request $request, GeneralSettings $settings) {

        if(!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');

        if($smallLogo) {
            $settings->small_logo_path = $smallLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if($bigLogo) {
            $settings->big_logo_path = $bigLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if($banner) {
            $settings->banner_path = $banner->storePublicly('banner', ['disk' => 'public']);
        }

        $settings->save();

        return Redirect::back()->with('success', 'Fotos hinzugefügt');

    }

    public function create_admin(Request $request, GeneralSettings $settings) {

        $logo = $request->file('logo');
        $banner = $request->file('banner');

        if($logo) {
            $settings->logo_path = $logo->storePublicly('logo', ['disk' => 'public']);
        }

        if($banner) {
            $settings->banner_path = $logo->storePublicly('banner', ['disk' => 'public']);
        }

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['string', 'max:15'],
            'password' => $this->passwordRules(),
            'position' => ['required', 'string', 'max:255'],
            'business' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:5000'],
        ]);

        $user = User::create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'phone_number' => $request['phone_number'],
            'password' => Hash::make($request['password']),
            'position' => $request['position'],
            'business' => $request['business'],
            'description' => $request['description'],
        ]);

        $this->guard->login($user);

        $user->assignRole('admin');

        $settings->setup_finished = true;
        $settings->company_name = $request['business'];
        $settings->save();
        return redirect(RouteServiceProvider::HOME);
    }
  }
