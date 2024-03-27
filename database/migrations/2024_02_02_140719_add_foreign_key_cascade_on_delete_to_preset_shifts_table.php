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
        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table
                ->foreign('shift_preset_id')
                ->references('id')
                ->on('shift_presets')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table->dropForeign('preset_shifts_shift_preset_id_foreign');
        });
    }
};
