<?php

use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'user_user_management_settings',
            function(Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->json('settings');
                $table->timestamps();
            }
        );
        /** @var UserUserManagementSettingService $userUserManagementSettingService */
        $userUserManagementSettingService = app()->make(UserUserManagementSettingService::class);
        /** @var UserService $userService */
        $userService = app()->make(UserService::class);

        foreach ($userService->getAllUsers() as $user) {
            $userUserManagementSettingService->updateOrCreateIfNecessary(
                $user,
                $userUserManagementSettingService->getDefaults()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()->make(UserUserManagementSettingService::class)->deleteAll();
    }
};
