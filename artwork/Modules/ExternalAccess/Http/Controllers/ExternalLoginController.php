<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Http\Requests\RequestLoginLinkRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalLoginService;
use Artwork\Modules\ExternalAccess\Services\ExternalScopeResolver;
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

    /**
     * Das Token wird hier nicht angefasst, damit ein GET (Link-Vorschau, Virenscanner) es nicht
     * entwertet; eingelöst wird per POST in redeem().
     */
    public function showRedeemConfirmation(string $token): Response
    {
        return Inertia::render('Auth/ConfirmLogin', [
            'token' => $token,
        ]);
    }

    public function redeem(string $token, ExternalScopeResolver $externalScopeResolver): RedirectResponse
    {
        $external = $this->externalLoginService->redeemToken($token);

        if ($external === null) {
            return redirect()->route('external.login.invalid');
        }

        return redirect()->to($this->landingUrlFor($external, $externalScopeResolver));
    }

    /**
     * Wer genau einen Tab und keine eigene Datenpflege hat (der Normalfall einer Tab-Einladung),
     * landet direkt im Tab; sonst auf der Übersicht.
     */
    private function landingUrlFor(ExternalAccess $external, ExternalScopeResolver $externalScopeResolver): string
    {
        $scopes = $externalScopeResolver->activeScopesFor($external)
            ->filter(fn (ExternalAccessScope $scope): bool => $scope->project !== null && $scope->projectTab !== null);

        if ($scopes->count() !== 1 || $external->isCrmAccessActive()) {
            return route('external.dashboard');
        }

        /** @var ExternalAccessScope $scope */
        $scope = $scopes->first();

        return route('external.project.tab.show', [
            'project' => $scope->project_id,
            'tab' => $scope->project_tab_id,
        ]);
    }

    public function logout(): RedirectResponse
    {
        $this->externalLoginService->logout();

        return redirect()->route('external.login.form');
    }
}
