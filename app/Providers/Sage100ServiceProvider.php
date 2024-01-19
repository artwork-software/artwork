<?php

namespace App\Providers;

use App\Sage100\Sage100;
use Illuminate\Support\ServiceProvider;

class Sage100ServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(Sage100::class, function () {
            return new Sage100(
                config('services.sage100.domain'),
                config('services.sage100.data_endpoint'),
                config('services.sage100.user'),
                config('services.sage100.password')
            );
        });
    }

    /**
     * @return array<Sage100>
     */
    public function provides(): array
    {
        return [Sage100::class];
    }
}
