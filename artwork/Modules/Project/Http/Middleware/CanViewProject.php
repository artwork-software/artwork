<?php

namespace Artwork\Modules\Project\Http\Middleware;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
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

        if (
            Auth::user()->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || (Auth::user()->hasPermissionTo(PermissionEnum::PROJECT_VIEW->value)
                || $project->users()->where('users.id', Auth::id())->first())
        ) {
            return $next($request);
        }
        if ($project->users()->where('users.id', Auth::id())->first()) {
            if ($project->users()->where('users.id', Auth::id())->first()->pivot->is_admin) {
                return $next($request);
            }
        }

        return redirect()->back();
    }
}
