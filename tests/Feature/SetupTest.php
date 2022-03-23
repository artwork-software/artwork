<?php

use App\Models\GeneralSettings;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('The first user can setup the app', function() {

   $this->post('/setup', [
        'name' => 'Benjamin',
        'email' => 'admin@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
        'phone_number' => '123123',
        'position' => "IT Administrator",
        'business' => 'DTH',
        'description' => 'Administrator',
        'logo' => UploadedFile::fake()->create('logo.jpg', 100),
        'banner' => UploadedFile::fake()->create('banner.jpg', 100),
    ]);
    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'name' => 'Benjamin',
        'email' => 'admin@example.com',
    ]);

    $user = User::where('email', 'admin@example.com',)->first();

    $this->assertTrue($user->hasRole('admin'));
    $this->assertTrue(app(GeneralSettings::class)->setup_finished);
    Storage::disk('public')->assertExists(app(GeneralSettings::class)->logo_path);
    Storage::disk('public')->assertExists(app(GeneralSettings::class)->banner_path);
});
