<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// ai-web-research (Entgrenzen P4): Web-Recherche des Assistenten, Default aus (Opt-in je Haus).
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ai_assistant.web_enabled', false);
    }

    public function down(): void
    {
        $this->migrator->delete('ai_assistant.web_enabled');
    }
};
