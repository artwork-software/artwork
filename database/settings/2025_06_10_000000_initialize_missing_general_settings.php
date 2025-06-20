<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Add missing email settings
        $this->migrator->add('general.invitation_email', '');
        $this->migrator->add('general.business_email', '');

        // Add budget account management setting
        $this->migrator->add('general.budget_account_management_global', false);

        // Add file mime types settings
        $this->migrator->add('general.allowed_project_file_mimetypes', ['*']);
        $this->migrator->add('general.allowed_room_file_mimetypes', ['*']);
        $this->migrator->add('general.allowed_branding_file_mimetypes', ['*']);
        $this->migrator->add('general.allowed_contract_file_mimetypes', ['*']);

        // Add file size settings (in MB)
        $this->migrator->add('general.allowed_project_file_size', 150);
        $this->migrator->add('general.allowed_room_file_size', 150);
        $this->migrator->add('general.allowed_branding_file_size', 150);
        $this->migrator->add('general.allowed_contract_file_size', 150);
    }
};
