<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_list_view_settings', function (Blueprint $table) {
            $table->boolean('hide_shift_row')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_list_view_settings', function (Blueprint $table) {
            $table->dropColumn('hide_shift_row');
        });
    }
};
