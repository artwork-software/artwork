<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();
        DB::table('settings')->insert(
            [
                'group' => 'general',
                'name' => 'budget_account_management_global',
                'locked' => 0,
                'payload' => json_encode(false),
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->delete(
            DB::table('settings')
                ->select(['id'])
                ->where('name', '=', 'budget_account_management_global')
                ->first()
                ->id
        );
    }
};
