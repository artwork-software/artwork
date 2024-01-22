<?php

namespace App\Http\Middleware;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanViewRoom
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
        $room = $request->route('room');

        if (
            Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionNameEnum::ROOM_UPDATE->value)
        ) {
            return $next($request);
        }
        if ($room->users()->where('users.id', Auth::id())->first()) {
            if ($room->users()->where('users.id', Auth::id())->first()->pivot->is_admin) {
                return $next($request);
            }
        }

        return redirect()->back();
    }
}
