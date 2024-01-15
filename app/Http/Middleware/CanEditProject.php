<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
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
            Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionNameEnum::WRITE_PROJECTS->value)
        ) {
            return $next($request);
        }

        if (!$project->users()->where('users.id', Auth::id())->first()->exists()) {
            return redirect()->back();
        }

        if (!$project->users()->where('users.id', Auth::id())->first()->pivot->can_write) {
            return redirect()->back();
        }
        return $next($request);
    }
}
