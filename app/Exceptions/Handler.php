<?php

namespace App\Exceptions;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingLockedException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use League\OAuth2\Server\Exception\OAuthServerException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        // Passport meldet abgelehnte/abgelaufene API-Tokens über den Handler; das ist
        // normales Client-Verhalten (401), kein Anwendungsfehler für Sentry
        OAuthServerException::class,
        // Gewollte Ablehnung (keine Gewerksplaner:in) mit Meldung an die Person — kein Fehler für Sentry
        \Artwork\Modules\Craft\Exceptions\CraftNotPlannableException::class,
        // Gewollte Sperren mit Toast/422 an die Person (Termin im Verkauf, CRM-Systemeintrag)
        TicketingLockedException::class,
        \Artwork\Modules\Crm\Exceptions\CrmSettingLockedException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // Eine Sperre aus dem Ticketing ist eine Nachricht an die Person, kein Fehler: Toast oder 422.
        $this->renderable(function (TicketingLockedException $e, $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        });

        // Mailserver nicht erreichbar/lehnt ab (Sentry HAU-ARTWORK-65, BERLINISCHE-GALERIE-3B): statt
        // 500-Seite eine Meldung an der Stelle, an der die Person war. Gemeldet wird weiterhin.
        // Typ im ersten Parameter wählt die Exception aus; benutzt wird nur $request
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
        $this->renderable(function (TransportExceptionInterface $e, $request) {
            $message = __(
                'The email could not be sent. Please check the mail settings or contact your administrator.'
            );

            if ($request->expectsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => $message], 503);
            }

            return $request->user() === null
                ? back()->withInput($request->except($this->dontFlash))->withErrors(['email' => $message])
                : back()->with('error', $message);
        });

        $this->reportable(function (Throwable $e): void {
            if (app()->bound('sentry')) {
                if (app()->environment('local') || app()->runningUnitTests()) {
                    return;
                }
                app('sentry')->captureException($e);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        if (!$request->expectsJson()) {
            // 403 innerhalb der App (Inertia-Navigation) als App-Seite statt nackter Laravel-Fehlerseite
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : null;
            if (($status === 403 || $e instanceof AuthorizationException) && $request->header('X-Inertia')) {
                return Inertia::render('Errors/ProjectError', [
                    'status' => 403,
                    'title' => 'No permission',
                    'message' => 'You do not have permission to open this page. Ask an admin for the required right.',
                    'homeHref' => Route::has('dashboard') ? route('dashboard') : url('/'),
                ])->toResponse($request)->setStatusCode(403);
            }

            if ($e instanceof ModelNotFoundException && $e->getModel() === Project::class) {
                $projectsIndexHref = Route::has('projects.index') ? route('projects.index') : url('/projects');
                $homeHref = Route::has('dashboard') ? route('dashboard') : url('/');

                return Inertia::render('Errors/ProjectError', [
                    'status' => 404,
                    'title' => 'Project not found',
                    'message' => 'The project does not exist (anymore) or you do not have access to it.',
                    'projectsIndexHref' => $projectsIndexHref,
                    'homeHref' => $homeHref,
                ])->toResponse($request)->setStatusCode(404);
            }
        }

        return parent::render($request, $e);
    }
}
