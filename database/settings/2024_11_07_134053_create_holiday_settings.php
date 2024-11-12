<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('holiday.subdivisions', []);
        $this->migrator->add('holiday.public_holidays', true);
        $this->migrator->add('holiday.school_holidays', true);
    }
};
