<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\ClientInvitation;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;

class AppController extends Controller
{
    use PasswordValidationRules;

    protected StatefulGuard $guard;

    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    public function index() {

        //setup process finished
        if(true) {
            return Redirect::route('login');
        } else {
            return Redirect::route('setup');
        }

    }

    public function setup_company() {

        //setup process finished
        if(true) {
            return Redirect::route('login');
        }

        return inertia('Auth/Register');
    }

    public function create_admin(Request $request) {

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
        ]);

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);

        $this->guard->login($user);

        return redirect(RouteServiceProvider::HOME);
    }
  }
