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
        Schema::table('shift_user', function (Blueprint $table) {
            $table->string('craft_abbreviation')->nullable()->after('shift_id');
        });

        Schema::table('shifts_service_providers', function (Blueprint $table) {
            $table->string('craft_abbreviation')->nullable()->after('shift_id');
        });

        Schema::table('shifts_freelancers', function (Blueprint $table) {
            $table->string('craft_abbreviation')->nullable()->after('shift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_user', function (Blueprint $table) {
            $table->dropColumn('craft_abbreviation');
        });

        Schema::table('shifts_service_providers', function (Blueprint $table) {
            $table->dropColumn('craft_abbreviation');
        });

        Schema::table('shifts_freelancers', function (Blueprint $table) {
            $table->dropColumn('craft_abbreviation');
        });
    }
};
