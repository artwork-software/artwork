<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insert([
            [
                'group' => 'general',
                'name' => 'inventory_show_inventory_number_as_name',
                'locked' => false,
                'payload' => 'false',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'general',
                'name' => 'inventory_number_prefix',
                'locked' => false,
                'payload' => '""',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->whereIn('name', [
                'inventory_show_inventory_number_as_name',
                'inventory_number_prefix',
            ])
            ->delete();
    }
};
