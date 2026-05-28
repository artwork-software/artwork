<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->boolean('is_bi_field')->default(false)->after('sidebar_enabled');
            $table->unsignedInteger('bi_order')->nullable()->after('is_bi_field');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['is_bi_field', 'bi_order']);
        });
    }
};
