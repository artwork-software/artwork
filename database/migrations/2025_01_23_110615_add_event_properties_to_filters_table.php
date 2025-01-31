<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->longText('eventProperties')->after('showAdjoiningRooms')->nullable();
            $table->dropColumn('isLoud');
            $table->dropColumn('isNotLoud');
            $table->dropColumn('hasAudience');
            $table->dropColumn('hasNoAudience');
        });
    }

    public function down(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->dropColumn('eventProperties');
            $table->boolean('isLoud')->default(false)->nullable();
            $table->boolean('isNotLoud')->default(false)->nullable();
            $table->boolean('hasAudience')->default(false)->nullable();
            $table->boolean('hasNoAudience')->default(false)->nullable();
        });
    }
};
