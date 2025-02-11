<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_statuses', function (Blueprint $table) {
            $table->boolean('default')->default(false);
        });

        // update first status to default
        DB::table('event_statuses')->where('id', 1)->update(['default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_statuses', function (Blueprint $table) {
            $table->dropColumn('default');
        });
    }
};
