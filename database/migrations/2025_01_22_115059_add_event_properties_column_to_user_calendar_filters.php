<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_filters', function (Blueprint $table) {
            $table->longText('event_properties')->after('room_categories')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_filters', function (Blueprint $table) {
            $table->dropColumn('event_properties');
        });
    }
};
