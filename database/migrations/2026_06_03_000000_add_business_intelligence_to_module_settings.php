<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insert([
            'group' => 'module_settings',
            'name' => 'business_intelligence',
            'locked' => false,
            'payload' => 'true',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'module_settings')
            ->where('name', 'business_intelligence')
            ->delete();
    }
};
