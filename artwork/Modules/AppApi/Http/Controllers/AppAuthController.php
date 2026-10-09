<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppLoginRequest;
use Artwork\Modules\AppApi\Services\AppTwoFactorAuthenticationService;
use Artwork\Modules\ExternalUserManagement\Service\CredentialLoginService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\AccessToken;

class AppAuthController extends Controller
{
    public function __construct(
        private readonly AppTwoFactorAuthenticationService $twoFactorAuthenticationService,
        private readonly CredentialLoginService $credentialLoginService,
    ) {
    }

    public function login(AppLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Same credential path as the web login form: LDAP bind, OIDC lockout, timing-safe local hash.
        try {
            $user = $this->credentialLoginService->attempt($validated['email'], $validated['password']);
        } catch (ValidationException $exception) {
            // OIDC accounts sign in via SSO only; the app has no SSO flow yet.
            return response()->json(['message' => collect($exception->errors())->flatten()->first()], 401);
        }

        if ($user === null) {
            // Uniform message — never reveal whether the email exists. Failed logins are
            // routine here: the app probes every known instance during discovery.
            return response()->json(['message' => __('These credentials do not match our records.')], 401);
        }

        if (
            !$this->twoFactorAuthenticationService->verify(
                $user,
                $validated['code'] ?? null,
                $validated['recovery_code'] ?? null,
            )
        ) {
            return response()->json([
                'message' => __('A valid two-factor authentication code is required.'),
                'two_factor_required' => true,
                'errors' => [
                    'code' => [__('The provided two factor authentication code was invalid.')],
                ],
            ], 422);
        }

        $token = $user->createToken($validated['device_name'], ['app'])->accessToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): Response
    {
        // App routes are bearer-only (api.app), so the current token is always a real access token.
        $token = $request->user()->token();
        if ($token instanceof AccessToken) {
            $token->revoke();
        }

        return response()->noContent();
    }

    /**
     * The app validates this shape (id, name, email, permissions) on the
     * device — removing or renaming fields breaks logins there. The permissions
     * block drives menu visibility in the app (e.g. hiding the Dienstplan for
     * workers without the shift plan permission).
     *
     * @return array{id: int, name: string, email: string, permissions: array{can_view_shift_plan: bool}}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'permissions' => [
                'can_view_shift_plan' => $user->can(PermissionEnum::VIEW_SHIFT_PLAN->value),
            ],
        ];
    }
}
