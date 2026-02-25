<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_view_calendar_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('project_status')->default(false);
            $table->boolean('options')->default(false);
            $table->boolean('project_management')->default(false);
            $table->boolean('repeating_events')->default(false);
            $table->boolean('work_shifts')->default(false);
            $table->boolean('description')->default(false);
            $table->boolean('use_project_time_period')->default(false);
            $table->integer('time_period_project_id')->default(0);
            $table->boolean('event_name')->default(true);
            $table->timestamps();
            $table->boolean('high_contrast')->default(false);
            $table->boolean('expand_days')->default(false);
            $table->boolean('use_event_status_color')->default(false);
            $table->boolean('project_artists')->default(false);
            $table->boolean('show_qualifications')->default(false);
            $table->boolean('shift_notes')->default(false);
            $table->boolean('hide_unoccupied_rooms')->default(false);
            $table->boolean('display_project_groups')->default(false);
            $table->boolean('show_unplanned_events')->default(false);
            $table->boolean('show_planned_events')->default(false);
            $table->boolean('hide_unoccupied_days')->default(false);
            $table->boolean('show_shift_group_tag')->default(false);
            $table->boolean('show_timeline')->default(false);
            $table->boolean('show_only_not_fully_staffed_shifts')->default(false);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Copy existing calendar_settings to daily_view for all users
        DB::statement('
            INSERT INTO user_daily_view_calendar_settings
                (user_id, project_status, options, project_management, repeating_events,
                 work_shifts, description, use_project_time_period, time_period_project_id,
                 event_name, created_at, updated_at, high_contrast, expand_days,
                 use_event_status_color, project_artists, show_qualifications, shift_notes,
                 hide_unoccupied_rooms, display_project_groups, show_unplanned_events,
                 show_planned_events, hide_unoccupied_days, show_shift_group_tag,
                 show_timeline, show_only_not_fully_staffed_shifts)
            SELECT
                user_id, project_status, options, project_management, repeating_events,
                work_shifts, description, use_project_time_period, time_period_project_id,
                event_name, created_at, updated_at, high_contrast, expand_days,
                use_event_status_color, project_artists, show_qualifications, shift_notes,
                hide_unoccupied_rooms, display_project_groups, show_unplanned_events,
                show_planned_events, hide_unoccupied_days, show_shift_group_tag,
                show_timeline, show_only_not_fully_staffed_shifts
            FROM user_calendar_settings
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_view_calendar_settings');
    }
};
