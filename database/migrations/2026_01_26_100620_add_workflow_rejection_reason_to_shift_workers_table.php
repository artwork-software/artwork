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
        Schema::table('shift_workers', function (Blueprint $table) {
            $table->text('workflow_rejection_reason')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('shift_workers', function (Blueprint $table) {
            $table->dropColumn('workflow_rejection_reason');
        });
    }
};
