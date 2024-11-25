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
        Schema::create('event_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->smallInteger('order');
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('event_status_id')->nullable()->constrained('event_statuses');
        });

        DB::table('event_statuses')->insert([
            ['name' => 'Ohne Status', 'color' => '#a7a6b1', 'order' => 1],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_statuses');

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_status_id']);
            $table->dropColumn('event_status_id');
        });
    }
};
