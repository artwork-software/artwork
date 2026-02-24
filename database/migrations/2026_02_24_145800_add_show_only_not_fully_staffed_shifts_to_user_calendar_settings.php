<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->boolean('show_only_not_fully_staffed_shifts')->default(false)->after('show_timeline');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('show_only_not_fully_staffed_shifts');
        });
    }
};
