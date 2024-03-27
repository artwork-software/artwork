<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_shift_calendar_filters', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('user_id');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_shift_calendar_filters', function (Blueprint $table): void {
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }
};
