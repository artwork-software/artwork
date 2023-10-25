<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Events\UserUpdated;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Mail\InvitationCreated;
use App\Models\Department;
use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{
    public function __construct(StatefulGuard $guard)
    {
        $this->authorizeResource(Invitation::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        request()->validate([
            'direction' => ['in:asc,desc', 'string'],
            'field' => ['in:name,created_at', 'string']
        ]);

        //This is necessary to enable sorting
        $query = Invitation::query();

        if (request()->has(['field', 'direction'])) {
            $query->orderBy(Str::of('invitations.')->append(request('field')), request('direction'));
        }

        return inertia('Users/Invitations', [
            'invitations' => $query->paginate(10)->through(fn ($invitation) => [
                'id' => $invitation->id,
                'name' => $invitation->name,
                'email' => $invitation->email,
                'created_at' => Carbon::parse($invitation->created_at)->format('d.m.Y')
            ])
        ]);
    }

    public function invite()
    {
        return inertia('Users/Invite', [
            'available_roles' => Role::all()->pluck('name'),
            'available_permissions' => Permission::all()->pluck('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreInvitationRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreInvitationRequest $request)
    {
        $admin_user = Auth::user();
        $permissions = $request->permissions;
        $roles = $request->roles;

        foreach ($request->user_emails as $email) {
            $token = createToken();

            $invitation = Invitation::create([
                'email' => $email,
                'token' => $token['hash'],
                'permissions' => json_encode($permissions),
                'roles' => json_encode($roles)
            ]);

            $invitation->departments()->sync(
                collect($request->departments)
                    ->map(function ($department) {

                        $this->authorize('update', Department::find($department['id']));

                        return $department['id'];
                    })
            );

            Mail::to($email)->send(new InvitationCreated($invitation, $admin_user, $token['plain']));
        }

        return Redirect::route('users')->with('success', 'Invitation created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Invitation $invitation)
    {
        return inertia('Users/InvitationEdit', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invitation  $invitation
     * @return RedirectResponse
     */
    public function update(Request $request, Invitation $invitation)
    {
        $oldEmail = $invitation->email;
        $newMail = $request->input('email');

        $invitation->update($request->only('email'));

        if ($newMail !== $oldEmail) {
            $token = createToken();
            Mail::to($newMail)->send(new InvitationCreated($invitation, Auth::user(), $token['plain']));
            $invitation->update(['token' => $token['hash']]);
        }

        return Redirect::route('user.invitations')->with('success', 'Invitation updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return RedirectResponse
     */
    public function destroy(Invitation $invitation)
    {
        $invitation->delete();

        return Redirect::back()->with('success', 'Invitation deleted');
    }

    public function accept(Request $request)
    {
        return inertia('Users/Accept', [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }

    public function createUser(AcceptInvitationRequest $request, StatefulGuard $guard)
    {
        /** @var Invitation $invitation */
        $invitation = Invitation::query()
            ->where('email', request()->email)
            ->with('departments')
            ->firstOrFail();

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

        $user->departments()->sync($invitation->departments->pluck('id'));


        $user->assignRole(json_decode($invitation->roles));
        $user->givePermissionTo(json_decode($invitation->permissions));
        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $invitation->delete();

        $guard->login($user);


        broadcast(new UserUpdated())->toOthers();

        return Redirect::route('dashboard')->with('success', 'Herzlich Willkommen.');
    }
}
