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
            $table->string('short_description')->nullable()->after('shift_id');
            $table->date('start_date')->nullable()->after('short_description');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
        });

        Schema::table('shifts_freelancers', function (Blueprint $table) {
            $table->string('short_description')->nullable()->after('shift_id');
            $table->date('start_date')->nullable()->after('short_description');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
        });

        Schema::table('shifts_service_providers', function (Blueprint $table) {
            $table->string('short_description')->nullable()->after('shift_id');
            $table->date('start_date')->nullable()->after('short_description');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_user', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'start_date', 'end_date', 'start_time', 'end_time']);
        });

        Schema::table('shifts_freelancers', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'start_date', 'end_date', 'start_time', 'end_time']);
        });

        Schema::table('shifts_service_providers', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'start_date', 'end_date', 'start_time', 'end_time']);
        });
    }
};
