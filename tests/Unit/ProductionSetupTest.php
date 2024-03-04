<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertDatabaseHas;

test('production seeder works', function (): void {

    Artisan::call('migrate:fresh --force');

    assertDatabaseMissing('permission_presets', [
        'name' => 'Standard User'
    ]);

    Artisan::call('db:seed:production');

    assertDatabaseHas('permission_presets', [
        'name' => 'Standard User'
    ]);
});
