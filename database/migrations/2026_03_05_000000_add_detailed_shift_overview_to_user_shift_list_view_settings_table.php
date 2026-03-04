<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_list_view_settings', function (Blueprint $table) {
            $table->boolean('detailed_shift_overview')->default(false)->after('show_fully_staffed_shifts');
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_list_view_settings', function (Blueprint $table) {
            $table->dropColumn('detailed_shift_overview');
        });
    }
};
