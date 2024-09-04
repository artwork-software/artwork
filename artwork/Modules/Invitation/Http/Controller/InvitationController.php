<?php

namespace Artwork\Modules\Invitation\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Invitation\Http\Requests\AcceptInvitationRequest;
use Artwork\Modules\Invitation\Http\Requests\StoreInvitationRequest;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Invitation\Services\InvitationService;
use Artwork\Modules\Permission\Services\PermissionService;
use Artwork\Modules\Role\Services\RoleService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Hashing\HashManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Response;
use Inertia\ResponseFactory;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Throwable;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly Redirector $redirector,
        private readonly ResponseFactory $responseFactory,
        private readonly RoleService $roleService,
        private readonly PermissionService $permissionService,
        private readonly UserService $userService,
        private readonly HashManager $hashManager
    ) {
        $this->authorizeResource(Invitation::class);
    }

    public function invite(): Response|ResponseFactory
    {
        return $this->responseFactory->render(
            'Users/Invite',
            [
                'available_roles' => $this->roleService->getAllRoleNames(),
                'available_permissions' => $this->permissionService->getAllPermissionNames(),
            ]
        );
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $this->invitationService->createOrUpdate(
            $request->get('permissions', []),
            $request->get('roles', []),
            $request->get('user_emails', []),
            $request->collect('departments')->pluck('id')
        );

        return $this->redirector->route('users');
    }

    public function accept(Request $request): Response|ResponseFactory
    {
        if (
            !($invitation = $this->invitationService->findByEmail($request->string('email'))) ||
            !$this->hashManager->check($request->query('token'), $invitation->getAttribute('token'))
        ) {
            throw new UnauthorizedException(401);
        }

        return $this->responseFactory->render(
            'Users/Accept',
            [
                'token' => $request->query('token'),
                'email' => $request->query('email'),
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function createUser(AcceptInvitationRequest $request): RedirectResponse
    {
        $invitation = $this->invitationService
            ->findByEmail($request->string('email'))
            ->load('departments');

        $this->userService->create(
            $request->userData(),
            $invitation->getAttribute('roles'),
            $invitation->getAttribute('permissions'),
            $invitation->getAttribute('departments')->pluck('id')->all()
        );

        $invitation->delete();

        return $this->redirector->route('dashboard');
    }
}
