<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\GeneralSettings;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;


class AppController extends Controller
{
    use PasswordValidationRules;

    protected StatefulGuard $guard;

    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:15'],
            'password' => $this->passwordRules(),
            'position' => ['required', 'string', 'max:255'],
            'business' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $user = User::create([
            'name' => $request['name'],
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
