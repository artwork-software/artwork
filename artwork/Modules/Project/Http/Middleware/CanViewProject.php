<?php

namespace Artwork\Modules\Project\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CanViewProject
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $project = $request->route('project');

        // Zutrittsregel zentral in ProjectPolicy::view (Projektteam inkl. Abteilungen,
        // globales view/write/management; Admins via Gate::before).
        if ($project !== null && Auth::user()?->can('view', $project)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'You do not have permission to access this project.');
        }

        return redirect()->back();
    }
}
