<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_rules', function (Blueprint $table) {
            $table->decimal('default_compensation_days', 3, 1)->nullable()->after('warning_color');
        });
    }

    public function down(): void
    {
        Schema::table('shift_rules', function (Blueprint $table) {
            $table->dropColumn('default_compensation_days');
        });
    }
};
