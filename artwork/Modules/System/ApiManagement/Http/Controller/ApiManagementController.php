<?php

namespace Artwork\Modules\System\ApiManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\System\ApiManagement\Http\Requests\StoreTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Laravel\Passport\Token;

/**
 * Verwaltung der Maschinen-Tokens (Tooleinstellungen → Schnittstellen).
 *
 * Die Übersicht liegt in ToolSettingsInterfacesController::index(); hier wohnen nur die
 * schreibenden Operationen.
 */
class ApiManagementController extends Controller
{
    public function store(StoreTokenRequest $request): RedirectResponse
    {
        $this->authorize('create', Token::class);

        // createToken() stammt aus Passports HasApiTokens und reicht den User-Provider korrekt an die
        // PersonalAccessTokenFactory durch. Der frühere Direktaufruf der Factory ließ den in Passport 13
        // hinzugekommenen vierten Parameter aus und warf deshalb bei jedem Versuch einen ArgumentCountError.
        $result = $request->user()->createToken(
            $request->string('name')->toString(),
            $request->validated('scopes', [])
        );

        // Gilt nur über oauth_access_tokens.expires_at (geprüft im ExtendedTokenValidator); der exp-Claim
        // im JWT behält Passports Standardlaufzeit. Ein reines Datum aus dem Formular gilt bis Tagesende.
        if ($expiresAt = $request->input('expires_at')) {
            $expiresAt = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $expiresAt)
                ? Carbon::parse($expiresAt)->endOfDay()
                : Carbon::parse($expiresAt);

            $result->token?->forceFill(['expires_at' => $expiresAt])->save();
        }

        // Einziger Moment, in dem der Klartext-Token existiert. Er wird bewusst nirgends persistiert:
        // Passport validiert eingehende Tokens über die JWT-Signatur und braucht keine Kopie.
        return back()
            ->with('success', __('API key created successfully.'))
            ->with('plainTextToken', $result->accessToken);
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Token::class);

        $token = Token::find($id);

        if (!$token) {
            return back()->with('error', __('Token not found.'));
        }

        $token->revoke();

        return back()->with('success', __('API key revoked successfully.'));
    }
}
