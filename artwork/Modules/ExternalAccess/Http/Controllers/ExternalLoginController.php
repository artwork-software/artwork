<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Http\Requests\RequestLoginLinkRequest;
use Artwork\Modules\ExternalAccess\Services\ExternalLoginService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExternalLoginController extends Controller
{
    public function __construct(
        private readonly ExternalLoginService $externalLoginService,
    ) {
    }

    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function requestLink(RequestLoginLinkRequest $request): RedirectResponse
    {
        $this->externalLoginService->requestLoginLink(
            strtolower(trim((string) $request->input('email'))),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('external.login.link-sent');
    }

    public function redeem(string $token): RedirectResponse
    {
        $external = $this->externalLoginService->redeemToken($token);

        if ($external === null) {
            return redirect()->route('external.login.invalid');
        }

        return redirect()->route('external.dashboard');
    }

    public function logout(): RedirectResponse
    {
        $this->externalLoginService->logout();

        return redirect()->route('external.login.form');
    }
}
