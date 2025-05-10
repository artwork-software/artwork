<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sage_assigned_data', function (Blueprint $t) {
            $t->boolean('is_collective_booking')->default(false)->after('buchungsdatum');
            $t->unsignedBigInteger('parent_booking_id')->nullable()->default(null)->after('column_cell_id');
            $t->unsignedBigInteger('column_cell_id')->nullable()->change();
        });

        Schema::table('sage_not_assigned_data', function (Blueprint $t) {
            $t->boolean('is_collective_booking')->default(false)->after('buchungsdatum');
            $t->unsignedBigInteger('parent_booking_id')->nullable()->default(null)->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sage_assigned_data', function (Blueprint $t) {
            $t->dropColumn('is_collective_booking');
            $t->dropColumn('parent_booking_id');
        });

        Schema::table('sage_not_assigned_data', function (Blueprint $t) {
            $t->dropColumn('is_collective_booking');
            $t->dropColumn('parent_booking_id');
        });
    }
};
