<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $now = Carbon::now();
        DB::table('settings')
            ->insert(
                [
                    'group' => 'general',
                    'name' => 'business_email',
                    'locked' => 0,
                    'payload' => json_encode(""),
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('name', 'business_email')
            ->delete();
    }
};
