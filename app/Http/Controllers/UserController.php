<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

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

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('Users/Index', [
            'users' => User::paginate(15)->through( fn($user) => [
                "name" => $user->name,
                "email" => $user->email,
                "position" => $user->position,
                "business" => $user->business,
                "phone_number" => $user->phone_number
            ])
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'position' => $user->position,
                'business' => $user->business,
                'description' => $user->description
            ]
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
        $user->update($request->only('name', 'phone_number', 'position', 'business', 'description'));

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

        return Redirect::back()->with('success', 'Benutzer gelöscht');
    }
}
