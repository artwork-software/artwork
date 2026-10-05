<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort auf „Passwort vergessen“, wenn der Password-Broker keinen Link verschickt hat
 * (unbekannte E-Mail, Throttle). Antwortet wie der Erfolgsfall, damit sich über das Formular
 * nicht ermitteln lässt, ob ein Konto existiert.
 */
class NeutralPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    public function __construct(protected string $status)
    {
    }

    // Signatur ohne nativen Typ wie im Fortify-Contract.
    public function toResponse($request): Response
    {
        $message = trans('passwords.sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }
}
