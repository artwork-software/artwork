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
        Schema::create('accommodation_accommodation_room_type', function (Blueprint $table) {
            $table->unsignedBigInteger('accommodation_id');
            $table->unsignedBigInteger('accommodation_room_type_id');

            // kurze, explizite FK-Namen
            $table->foreign('accommodation_id', 'fk_acc_room_acc')
                ->references('id')->on('accommodations')
                ->onDelete('cascade');

            $table->foreign('accommodation_room_type_id', 'fk_acc_room_type')
                ->references('id')->on('accommodation_room_types')
                ->onDelete('cascade');

            // optional, aber üblich für Pivot-Tabellen:
            $table->primary(['accommodation_id', 'accommodation_room_type_id'], 'pk_acc_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_accommodation_room_type');
    }
};
