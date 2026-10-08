<?php

namespace Artwork\Modules\Project\Http\Middleware;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CanEditProject
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');
        $user = Auth::user();

        if (
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->hasPermissionTo(PermissionEnum::WRITE_PROJECTS->value)
            || $project->users()->where('users.id', $user->id)->first()?->pivot->can_write
        ) {
            return $next($request);
        }

        // Ein JSON-Aufruf kann mit einer Weiterleitung nichts anfangen.
        return $request->expectsJson() ? abort(403) : redirect()->back();
    }
}
