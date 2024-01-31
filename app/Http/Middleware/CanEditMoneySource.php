<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanEditMoneySource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $project = $request->route('project');

        if (
            Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value)
        ) {
            return $next($request);
        }
        if ($project->users()->where('users.id', Auth::id())->first()) {
            if ($project->users()->where('users.id', Auth::id())->first()->pivot->access_budget) {
                return $next($request);
            }
        }

        return redirect()->back();
    }
}
