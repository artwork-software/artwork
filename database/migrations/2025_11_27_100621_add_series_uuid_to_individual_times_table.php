<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individual_times', function (Blueprint $table) {
            $table->uuid('series_uuid')
                ->nullable()
                ->after('id');

            $table->index('series_uuid');

            $table->foreign('series_uuid')
                ->references('uuid')
                ->on('individual_time_series')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('individual_times', function (Blueprint $table) {
            $table->dropForeign(['series_uuid']);
            $table->dropIndex(['series_uuid']);
            $table->dropColumn('series_uuid');
        });
    }
};
