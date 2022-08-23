<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function search(SearchRequest $request) {

        $this->authorize('viewAny',User::class);

        return User::search($request->input('query'))->get()->map(fn($user) => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            "profile_photo_url" => $user->profile_photo_url,
            "email" => $user->email,
            'departments' => $user->departments,
            "position" => $user->position,
            "business" => $user->business,
            "phone_number" => $user->phone_number
        ]);
    }

    public function reset_user_password(Request $request) {

        $this->authorize('update',User::class);

        $request->validate([Fortify::email() => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only(Fortify::email())
        );

        return $status == Password::RESET_LINK_SENT
            ? Redirect::back()->with('status', __('passwords.sent_to_user', ['email' => $request->email]))
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('Users/Index', [
            'users' => User::all()->map(fn($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                "profile_photo_url" => $user->profile_photo_url,
                "email" => $user->email,
                'departments' => $user->departments,
                "position" => $user->position,
                "business" => $user->business,
                "phone_number" => $user->phone_number
            ]),
            "all_permissions" => Permission::all()->groupBy('group'),
            "departments" => Department::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param User $user
     * @return Response|ResponseFactory
     */
    public function edit(User $user): Response|ResponseFactory
    {
        return inertia('Users/Edit', [
            'user_to_edit' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                "profile_photo_url" => $user->profile_photo_url,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'position' => $user->position,
                'business' => $user->business,
                'description' => $user->description,
                'departments' => $user->departments,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getPermissionNames(),
            ],
            "departments" => Department::all(),
            "password_reset_status" => session('status'),
            'available_roles' => Role::all(),
            "all_permissions" => Permission::all()->groupBy('group'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->only('first_name','last_name', 'phone_number', 'position', 'business', 'description'));

        $user->departments()->sync(
            collect($request->departments)
                ->map(function ($department) {

                    $this->authorize('update', Department::find($department['id']));

                    return $department['id'];
                })
        );

        $user->syncPermissions($request->permissions);
        $user->syncRoles($request->roles);

        return Redirect::route('user.edit',$user)->with('success', 'Benutzer aktualisiert');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return Redirect::route('users')->with('success', 'Benutzer gelöscht');
    }
}
