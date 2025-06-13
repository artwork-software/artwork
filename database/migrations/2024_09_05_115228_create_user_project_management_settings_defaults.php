<?php

use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var UserProjectManagementSettingService $userProjectManagementSettingService */
        $userProjectManagementSettingService = app()->make(UserProjectManagementSettingService::class);
        /** @var UserService $userService */
        $userService = app()->make(UserService::class);

        foreach ($userService->getAllUsers() as $user) {
            $userProjectManagementSettingService->updateOrCreateIfNecessary(
                $user,
                $userProjectManagementSettingService->getDefaults()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()->make(UserProjectManagementSettingService::class)->deleteAll();
    }
};
