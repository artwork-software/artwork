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
        Schema::table('user_shift_calendar_abos', function (Blueprint $table) {
            $table->string('calendar_abo_id')->nullable()->change();
        });

        Schema::table('user_calendar_abos', function (Blueprint $table) {
            $table->string('calendar_abo_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_shift_calendar_abos', function (Blueprint $table) {
            $table->dropColumn('calendar_abo_id');
        });
        Schema::table('user_calendar_abos', function (Blueprint $table) {
            $table->dropColumn('calendar_abo_id');
        });
    }
};
