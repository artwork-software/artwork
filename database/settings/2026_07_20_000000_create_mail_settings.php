<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('mail.host', null);
        $this->migrator->add('mail.port', null);
        $this->migrator->add('mail.encryption', null);
        $this->migrator->add('mail.username', null);
        $this->migrator->add('mail.password', null);
        $this->migrator->add('mail.from_address', null);
        $this->migrator->add('mail.from_name', null);
    }

    public function down(): void
    {
        foreach ([
            'host',
            'port',
            'encryption',
            'username',
            'password',
            'from_address',
            'from_name',
        ] as $key) {
            $this->migrator->delete("mail.{$key}");
        }
    }
};
