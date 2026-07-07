<?php

namespace App\Exceptions;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            if (app()->bound('sentry')) {
                if (env('APP_ENV', 'local') === 'local' || app()->runningUnitTests()) {
                    return;
                }
                app('sentry')->captureException($e);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        if (!$request->expectsJson()) {
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
