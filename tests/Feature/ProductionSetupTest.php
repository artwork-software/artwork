<?php

namespace Tests\Unit;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertDatabaseHas;

test('production seeder works', function (): void {
    if (User::first() !== null) {
        return;
    }

    Artisan::call('migrate:fresh --force');

    assertDatabaseMissing('permission_presets', [
        'name' => 'Standard User'
    ]);

    Artisan::call('db:seed:production');

    assertDatabaseHas('permission_presets', [
        'name' => 'Standard User'
    ]);
});
