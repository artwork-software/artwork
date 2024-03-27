<?php

namespace Artwork\Modules\Setup;

use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\Setup\DataProvider\RoleAndPermissionDataProvider;
use Illuminate\Support\ServiceProvider;

class SetupServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(RoleAndPermissionDataProvider::class, fn() => new BaseDataProvider());
    }
}
