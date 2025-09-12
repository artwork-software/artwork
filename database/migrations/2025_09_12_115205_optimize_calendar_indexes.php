<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['room_id', 'start_time'], 'idx_events_room_start');
            $table->index(['room_id', 'end_time'],   'idx_events_room_end');
            $table->index('start_time',              'idx_events_start');
            $table->index('end_time',                'idx_events_end');
            $table->index('event_type_id',           'idx_events_type');
            $table->index('event_status_id',         'idx_events_status');
            $table->index('is_planning',             'idx_events_planning');
            $table->index('deleted_at',              'idx_events_deleted_at');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index('relevant_for_disposition', 'idx_rooms_disposition');
            $table->index('position',                 'idx_rooms_position');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_room_start');
            $table->dropIndex('idx_events_room_end');
            $table->dropIndex('idx_events_start');
            $table->dropIndex('idx_events_end');
            $table->dropIndex('idx_events_type');
            $table->dropIndex('idx_events_status');
            $table->dropIndex('idx_events_planning');
            $table->dropIndex('idx_events_deleted_at');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_disposition');
            $table->dropIndex('idx_rooms_position');
        });
    }
};
