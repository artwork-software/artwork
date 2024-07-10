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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('num_of_guests');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('entry_fee');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('registration_required');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('register_by');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('registration_deadline');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('closed_society');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('num_of_guests')->nullable()->default(null);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('entry_fee')->nullable()->default(null);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('registration_required')->nullable()->default(null);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('register_by')->nullable()->default(null);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('registration_deadline')->nullable()->default(null);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('closed_society')->nullable()->default(null);
        });
    }
};
