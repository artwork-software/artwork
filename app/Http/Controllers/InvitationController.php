<?php

namespace App\Http\Controllers;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Invitation\Http\Requests\AcceptInvitationRequest;
use Artwork\Modules\Invitation\Http\Requests\StoreInvitationRequest;
use Artwork\Modules\Invitation\Mail\InvitationCreated;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Events\UserUpdated;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invitation::class);
    }

    public function index(): Response|ResponseFactory
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

    public function invite(): Response|ResponseFactory
    {
        return inertia('Users/Invite', [
            'available_roles' => Role::all()->pluck('name'),
            'available_permissions' => Permission::all()->pluck('name'),
        ]);
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $admin_user = Auth::user();
        $permissions = $request->permissions;
        $roles = $request->roles;

        foreach ($request->user_emails as $email) {
            $token = $this->createToken();

            $invitation = Invitation::create([
                'email' => $email,
                'token' => $token['hash'],
                'permissions' => $permissions,
                'roles' => $roles
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

        return Redirect::route('users');
    }

    public function edit(Invitation $invitation): Response|ResponseFactory
    {
        return inertia('Users/InvitationEdit', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
            ]
        ]);
    }

    public function update(Request $request, Invitation $invitation): RedirectResponse
    {
        $oldEmail = $invitation->email;
        $newMail = $request->input('email');

        $invitation->update($request->only('email'));

        if ($newMail !== $oldEmail) {
            $token = $this->createToken();
            Mail::to($newMail)->send(new InvitationCreated($invitation, Auth::user(), $token['plain']));
            $invitation->update(['token' => $token['hash']]);
        }

        return Redirect::route('user.invitations');
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        return Redirect::back();
    }

    public function accept(Request $request): Response|ResponseFactory
    {
        return inertia('Users/Accept', [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }

    public function createUser(AcceptInvitationRequest $request, StatefulGuard $guard): RedirectResponse
    {
        /** @var Invitation $invitation */
        $invitation = Invitation::query()
            ->where('email', $request->email)
            ->with('departments')
            ->firstOrFail();

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

        $user->departments()->sync($invitation->departments->pluck('id'));

        $user->assignRole(...$invitation->roles);
        $user->givePermissionTo(...$invitation->permissions);
        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $invitation->delete();

        $guard->login($user);

        broadcast(new UserUpdated())->toOthers();

        return Redirect::route('dashboard');
    }

    /**
     * @return array<string, string>
     */
    private function createToken(): array
    {
        do {
            $tokenPlain = Str::random(20);
            $hashedToken = Hash::make($tokenPlain);
        } while (Invitation::where('token', $hashedToken)->first());

        return [
            'plain' => $tokenPlain,
            'hash' => $hashedToken
        ];
    }
}
