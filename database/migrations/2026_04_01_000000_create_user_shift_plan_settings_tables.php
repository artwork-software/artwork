<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_shift_plan_settings', function (Blueprint $table) {
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
            $table->boolean('use_main_category_color')->default(false);
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
            $table->boolean('show_user_overview')->default(true);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('user_shift_plan_daily_settings', function (Blueprint $table) {
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
            $table->boolean('use_main_category_color')->default(false);
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

        // Seed shift plan settings from existing calendar settings
        try {
            DB::statement('
                INSERT INTO user_shift_plan_settings
                    (user_id, project_status, options, project_management, repeating_events,
                     work_shifts, description, use_project_time_period, time_period_project_id,
                     event_name, created_at, updated_at, high_contrast, expand_days,
                     use_event_status_color, use_main_category_color, project_artists,
                     show_qualifications, shift_notes, hide_unoccupied_rooms,
                     display_project_groups, show_unplanned_events, show_planned_events,
                     hide_unoccupied_days, show_shift_group_tag, show_timeline,
                     show_only_not_fully_staffed_shifts, show_user_overview)
                SELECT
                    cs.user_id, cs.project_status, cs.options, cs.project_management, cs.repeating_events,
                    cs.work_shifts, cs.description, cs.use_project_time_period, cs.time_period_project_id,
                    cs.event_name, cs.created_at, cs.updated_at, cs.high_contrast, cs.expand_days,
                    cs.use_event_status_color, cs.use_main_category_color, cs.project_artists,
                    cs.show_qualifications, cs.shift_notes, cs.hide_unoccupied_rooms,
                    cs.display_project_groups, cs.show_unplanned_events, cs.show_planned_events,
                    cs.hide_unoccupied_days, cs.show_shift_group_tag, cs.show_timeline,
                    cs.show_only_not_fully_staffed_shifts, cs.show_user_overview
                FROM user_calendar_settings cs
                INNER JOIN users u ON u.id = cs.user_id
                WHERE cs.user_id NOT IN (SELECT user_id FROM user_shift_plan_settings)
            ');
        } catch (Throwable $exception) {
            report($exception);
        }

        // Seed shift plan daily settings from existing daily view calendar settings
        try {
            DB::statement('
                INSERT INTO user_shift_plan_daily_settings
                    (user_id, project_status, options, project_management, repeating_events,
                     work_shifts, description, use_project_time_period, time_period_project_id,
                     event_name, created_at, updated_at, high_contrast, expand_days,
                     use_event_status_color, use_main_category_color, project_artists,
                     show_qualifications, shift_notes, hide_unoccupied_rooms,
                     display_project_groups, show_unplanned_events, show_planned_events,
                     hide_unoccupied_days, show_shift_group_tag, show_timeline,
                     show_only_not_fully_staffed_shifts)
                SELECT
                    dv.user_id, dv.project_status, dv.options, dv.project_management, dv.repeating_events,
                    dv.work_shifts, dv.description, dv.use_project_time_period, dv.time_period_project_id,
                    dv.event_name, dv.created_at, dv.updated_at, dv.high_contrast, dv.expand_days,
                    dv.use_event_status_color, dv.use_main_category_color, dv.project_artists,
                    dv.show_qualifications, dv.shift_notes, dv.hide_unoccupied_rooms,
                    dv.display_project_groups, dv.show_unplanned_events, dv.show_planned_events,
                    dv.hide_unoccupied_days, dv.show_shift_group_tag, dv.show_timeline,
                    dv.show_only_not_fully_staffed_shifts
                FROM user_daily_view_calendar_settings dv
                INNER JOIN users u ON u.id = dv.user_id
                WHERE dv.user_id NOT IN (SELECT user_id FROM user_shift_plan_daily_settings)
            ');
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shift_plan_daily_settings');
        Schema::dropIfExists('user_shift_plan_settings');
    }
};
