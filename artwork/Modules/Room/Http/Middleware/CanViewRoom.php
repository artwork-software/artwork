<?php

namespace Artwork\Modules\Room\Http\Middleware;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
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
     *
     */
    public function handle(Request $request, Closure $next)
    {
        $room = $request->route('room');

        if (
            Auth::user()->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || Auth::user()->hasPermissionTo(PermissionEnum::ROOM_UPDATE->value)
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
