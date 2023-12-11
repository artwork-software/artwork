<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Auth;

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
            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
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
