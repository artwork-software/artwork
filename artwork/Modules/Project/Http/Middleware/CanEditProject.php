<?php

namespace Artwork\Modules\Project\Http\Middleware;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanEditProject
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $project = $request->route('project');

        if (
            Auth::user()->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionEnum::WRITE_PROJECTS->value)
        ) {
            return $next($request);
        }

        if ($project->users()->where('users.id', Auth::id())->first() === null) {
            return redirect()->back();
        }

        if ($project->users()->where('users.id', Auth::id())->first()) {
            if ($project->users()->where('users.id', Auth::id())->first()->pivot->can_write) {
                return $next($request);
            }
        }
        return redirect()->back();
    }
}
