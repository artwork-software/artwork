<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antwort auf „Passwort vergessen“, wenn der Password-Broker KEINEN Link verschickt hat
 * (unbekannte E-Mail, Broker-Throttle). Antwortet bewusst genauso wie der Erfolgsfall
 * (gleicher Redirect, gleicher Status-Flash, kein Validierungsfehler), damit sich über das
 * Formular nicht ermitteln lässt, ob zu einer E-Mail-Adresse ein Konto existiert
 * (Sicherheits-Audit 21.09.2026, Abschnitt D).
 */
class NeutralPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    public function __construct(protected string $status)
    {
    }

    // Signatur ohne nativen Typ wie im Fortify-Contract (Parameter darf nicht verengt werden).
    public function toResponse($request): Response
    {
        $message = trans('passwords.sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }
}
