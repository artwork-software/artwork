<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    /** @var string[] */
    private array $keys = [
        'enabled',
        'level_b_enabled',
        'level_c_enabled',
        'transcript_retention_days',
        'ai_session_client_id',
    ];

    public function up(): void
    {
        $this->migrator->add('ai_assistant.enabled', false);
        $this->migrator->add('ai_assistant.level_b_enabled', false);
        $this->migrator->add('ai_assistant.level_c_enabled', false);
        $this->migrator->add('ai_assistant.transcript_retention_days', 0);
        $this->migrator->add('ai_assistant.ai_session_client_id', null);
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            $this->migrator->delete("ai_assistant.{$key}");
        }
    }
};
