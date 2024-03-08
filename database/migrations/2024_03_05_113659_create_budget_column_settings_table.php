<?php

use Carbon\Carbon;
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
        Schema::create('budget_column_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('column_position');
            $table->text('column_name');
            $table->timestamps();
        });

        $now = Carbon::now();
        DB::table('budget_column_settings')->insert(
            [
                'column_position' => 0,
                'column_name' => 'KTO',
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
        DB::table('budget_column_settings')->insert(
            [
                'column_position' => 1,
                'column_name' => 'KST',
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
        DB::table('budget_column_settings')->insert(
            [
                'column_position' => 2,
                'column_name' => 'Position',
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
        Schema::dropIfExists('budget_column_settings');
    }
};
