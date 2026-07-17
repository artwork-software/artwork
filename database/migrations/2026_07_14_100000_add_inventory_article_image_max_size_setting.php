<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'inventory_article_image_max_size_mb')
            ->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'group' => 'general',
                'name' => 'inventory_article_image_max_size_mb',
                'locked' => false,
                'payload' => '10',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'inventory_article_image_max_size_mb')
            ->delete();
    }
};
