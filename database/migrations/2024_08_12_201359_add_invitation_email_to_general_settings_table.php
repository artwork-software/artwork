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
        DB::table('settings')
            ->insert(
                [
                    'group' => 'general',
                    'name' => 'invitation_email',
                    'locked' => 0,
                    'payload' => json_encode(""),
                    'created_at' => ($now = Carbon::now()),
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
            ->where('name', 'invitation_email')
            ->delete();
    }
};
