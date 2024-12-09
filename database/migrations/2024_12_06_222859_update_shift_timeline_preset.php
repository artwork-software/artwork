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
        Schema::table('shift_preset_timelines', function (Blueprint $table) {
            $table->dateTime('end')->nullable()->after('start')->change();
        });

        Schema::table('shift_presets', function (Blueprint $table) {
            // remove event_type_id column
            $table->dropColumn('event_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_preset_timelines', function (Blueprint $table) {
            //
        });
    }
};
