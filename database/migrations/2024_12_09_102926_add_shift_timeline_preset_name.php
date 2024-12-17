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
            $table->string('name')->after('id');
            // drop current columns
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('start');
            $table->dropColumn('end');

            // drop shift_preset_id foreign key
            $table->dropForeign('shift_preset_timelines_shift_preset_id_foreign');
            $table->dropColumn('shift_preset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_preset_timelines', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
