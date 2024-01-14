<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::inertia()->whenRendering(
            'Profile/Show',
            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
            function (Request $request, array $data) {
                return array_merge($data, [
                    "all_departments" => Department::all(),
                    "user_departments" => Auth::user() -> departments,
                ]);
            }
        );

        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
