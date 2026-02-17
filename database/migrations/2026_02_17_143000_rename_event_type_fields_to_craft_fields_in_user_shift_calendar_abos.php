<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_shift_calendar_abos', function (Blueprint $table) {
            $table->renameColumn('specific_event_types', 'specific_crafts');
            $table->renameColumn('event_types', 'craft_ids');
        });
    }

    public function down(): void
    {
        Schema::table('user_shift_calendar_abos', function (Blueprint $table) {
            $table->renameColumn('specific_crafts', 'specific_event_types');
            $table->renameColumn('craft_ids', 'event_types');
        });
    }
};
