<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.allowed_project_file_mimetypes', ['*']);
        $this->migrator->add('general.allowed_room_file_mimetypes', ['*']);
        $this->migrator->add('general.allowed_branding_file_mimetypes', ['*']);
    }
};
