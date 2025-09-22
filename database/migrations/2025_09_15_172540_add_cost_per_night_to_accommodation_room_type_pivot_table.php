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
        Schema::table('accommodation_accommodation_room_type', function (Blueprint $table) {
            $table->decimal('cost_per_night', 8, 2)->default(0.00)->after('accommodation_room_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodation_accommodation_room_type', function (Blueprint $table) {
            $table->dropColumn('cost_per_night');
        });
    }
};
