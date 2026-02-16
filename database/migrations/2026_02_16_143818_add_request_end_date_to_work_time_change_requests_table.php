<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_time_change_requests', function (Blueprint $table) {
            $table->date('request_end_date')->nullable()->after('request_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_time_change_requests', function (Blueprint $table) {
            $table->dropColumn('request_end_date');
        });
    }
};
