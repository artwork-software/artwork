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
            $table->dropColumn('entry_fee');
            $table->dropColumn('registration_required');
            $table->dropColumn('register_by');
            $table->dropColumn('registration_deadline');
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
            $table->string('entry_fee')->nullable()->default(null);
            $table->boolean('registration_required')->nullable()->default(false);
            $table->string('register_by')->nullable()->default(null);
            $table->string('registration_deadline')->nullable()->default(null);
            $table->boolean('closed_society')->nullable()->default(false);
        });
    }
};
