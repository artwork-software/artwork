<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Spatie\LaravelSettings\Migrations\SettingsMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->migrator->add('general.allowed_project_file_size', 150);
        $this->migrator->add('general.allowed_room_file_size', 150);
        $this->migrator->add('general.allowed_branding_file_size', 150);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
