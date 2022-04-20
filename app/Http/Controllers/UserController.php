<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
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

        return User::search($request->input('query'))->get();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('Users/Index', [
            'users' => User::paginate(15)->through( fn($user) => [
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
            "departments" => Department::all()
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
                'available_roles' => Role::all()->pluck('name'),
                'permissions' => $user->getPermissionNames(),
                'available_permissions' => Permission::all()->pluck('name'),
            ],
            "departments" => Department::all()
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
        $user->assignRole($request->role);

        return Redirect::route('users')->with('success', 'Benutzer aktualisiert');
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
