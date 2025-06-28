<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Add missing email settings
        $this->addIfNotExists('general.invitation_email', '');
        $this->addIfNotExists('general.business_email', '');

        // Add budget account management setting
        $this->addIfNotExists('general.budget_account_management_global', false);

        // Add file mime types settings
        $this->addIfNotExists('general.allowed_project_file_mimetypes', ['*']);
        $this->addIfNotExists('general.allowed_room_file_mimetypes', ['*']);
        $this->addIfNotExists('general.allowed_branding_file_mimetypes', ['*']);
        $this->addIfNotExists('general.allowed_contract_file_mimetypes', ['*']);

        // Add file size settings (in MB)
        $this->addIfNotExists('general.allowed_project_file_size', 150);
        $this->addIfNotExists('general.allowed_room_file_size', 150);
        $this->addIfNotExists('general.allowed_branding_file_size', 150);
        $this->addIfNotExists('general.allowed_contract_file_size', 150);
    }

    /**
     * Add a setting only if it doesn't already exist
     *
     * @param string $property
     * @param mixed $value
     * @param bool $encrypted
     * @return void
     */
    private function addIfNotExists(string $property, $value = null, bool $encrypted = false): void
    {
        try {
            $this->migrator->add($property, $value, $encrypted);
        } catch (SettingAlreadyExists $e) {
            // Setting already exists, do nothing
        }
    }
};
