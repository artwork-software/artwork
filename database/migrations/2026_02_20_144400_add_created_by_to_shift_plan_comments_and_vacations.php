<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_plan_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('date');
        });

        Schema::table('vacations', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('shift_plan_comments', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('vacations', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
