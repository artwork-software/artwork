<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Agent;

/**
 * Ersatz für Jetstreams „andere Browser-Sitzungen abmelden“: Jetstream läuft hier mit dem Passport-Guard
 * „api“ und ist für Web-Sessions tot. Betrifft ausschließlich den eingeloggten Nutzer.
 */
class UserBrowserSessionController extends Controller
{
    /**
     * Nur mit Session-Driver „database“.
     */
    public function index(Request $request): JsonResponse
    {
        if (config('session.driver') !== 'database') {
            return response()->json(['sessions' => [], 'supported' => false]);
        }

        $currentId = $request->session()->getId();

        $sessions = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) use ($currentId): array {
                $agent = tap(new Agent(), fn (Agent $agent) => $agent->setUserAgent((string) $session->user_agent));

                return [
                    'agent' => [
                        'is_desktop' => $agent->isDesktop(),
                        'platform' => $agent->platform(),
                        'browser' => $agent->browser(),
                    ],
                    'ip_address' => $session->ip_address,
                    'is_current_device' => $session->id === $currentId,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                ];
            })
            ->values();

        return response()->json(['sessions' => $sessions, 'supported' => true]);
    }

    public function destroy(Request $request, StatefulGuard $guard): RedirectResponse|JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        // IdP-Konten (LDAP/OIDC) haben kein lokales Passwort – dann ist keine Bestätigung möglich.
        if ($user->password === null || $user->password === '') {
            throw ValidationException::withMessages([
                'password' => __(
                    'This account has no local password. Other sessions can only be logged out by changing '
                    . 'the password at the identity provider.'
                ),
            ]);
        }

        if (!Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $guard->logoutOtherDevices((string) $request->input('password'));

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => __('Other browser sessions have been logged out.')]);
        }

        return back(303)->with('status', __('Other browser sessions have been logged out.'));
    }
}
