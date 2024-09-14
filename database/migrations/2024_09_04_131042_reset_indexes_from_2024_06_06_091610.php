<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverts: database/migrations/2024_06_06_091610_set_indexes.php
     */
    public function up(): void
    {
        Schema::table('adjoining_room_main_room', static function (Blueprint $table) {
            $table->dropForeign('adjoining_room_main_room_adjoining_room_id_foreign');
            $table->dropForeign('adjoining_room_main_room_main_room_id_foreign');

            $table->dropIndex('adjoining_room_main_room_adjoining_room_id_foreign');
            $table->dropIndex('adjoining_room_main_room_main_room_id_foreign');

        });

        Schema::table('adjoining_room_main_room', static function (Blueprint $table) {
            $table->integer('adjoining_room_id')->change();
            $table->integer('main_room_id')->change();
        });

        Schema::table('availabilities', static function (Blueprint $table) {
            $table->dropForeign('availabilities_series_id_foreign');

            $table->dropIndex('availabilities_series_id_foreign');
        });

        Schema::table('availabilities', static function (Blueprint $table) {
            $table->integer('series_id')->change();
        });

        Schema::table('availabilities_conflicts', static function (Blueprint $table) {
            $table->dropForeign('availabilities_conflicts_availability_id_foreign');
            $table->dropForeign('availabilities_conflicts_shift_id_foreign');

            $table->dropIndex('availabilities_conflicts_availability_id_foreign');
            $table->dropIndex('availabilities_conflicts_shift_id_foreign');
        });

        Schema::table('category_project', static function (Blueprint $table) {
            $table->dropForeign('category_project_category_id_foreign');
            $table->dropForeign('category_project_project_id_foreign');

            $table->dropIndex('category_project_category_id_foreign');
            $table->dropIndex('category_project_project_id_category_id_index');
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->dropForeign('cell_calculations_cell_id_foreign');

            $table->dropIndex('cell_calculations_cell_id_foreign');
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->bigInteger('cell_id')->change();
        });

        Schema::table('checklist_template_user', static function (Blueprint $table) {
            $table->dropForeign('checklist_template_user_checklist_template_id_foreign');
            $table->dropForeign('checklist_template_user_user_id_foreign');

            $table->dropIndex('checklist_template_user_checklist_template_id_foreign');
            $table->dropIndex('checklist_template_user_user_id_foreign');
        });

        Schema::table('checklist_user', static function (Blueprint $table) {
            $table->dropForeign('checklist_user_checklist_id_foreign');
            $table->dropForeign('checklist_user_user_id_foreign');

            $table->dropIndex('checklist_user_checklist_id_foreign');
            $table->dropIndex('checklist_user_user_id_foreign');
        });

        Schema::table('checklist_templates', static function (Blueprint $table) {
            $table->dropForeign('checklist_templates_user_id_foreign');

            $table->dropIndex('checklist_templates_user_id_foreign');
        });

        Schema::table('checklists', static function (Blueprint $table) {
            $table->dropForeign('checklists_project_id_foreign');
            $table->dropForeign('checklists_user_id_foreign');

            $table->dropIndex('checklists_project_id_foreign');
            $table->dropIndex('checklists_user_id_foreign');
        });

        Schema::table('column_sub_position_row', static function (Blueprint $table) {
            $table->dropIndex('column_sub_position_row_column_id_foreign');
            $table->dropIndex('column_sub_position_row_linked_money_source_id_foreign');
            $table->dropIndex('column_sub_position_row_sub_position_row_id_foreign');
        });

        Schema::table('column_sub_position_row', static function (Blueprint $table) {
            $table->bigInteger('column_id')->change();
            $table->bigInteger('sub_position_row_id')->change();
            $table->bigInteger('linked_money_source_id')->change();
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->dropForeign('columns_locked_by_foreign');
            $table->dropForeign('columns_table_id_foreign');

            $table->dropIndex('columns_locked_by_foreign');
            $table->dropIndex('columns_table_id_foreign');
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->bigInteger('table_id')->change();
        });

        Schema::table('comments', static function (Blueprint $table) {
            $table->dropForeign('comments_project_file_id_foreign');
            $table->dropForeign('comments_project_id_foreign');
            $table->dropForeign('comments_user_id_foreign');

            $table->dropIndex('comments_project_file_id_foreign');
            $table->dropIndex('comments_project_id_foreign');
            $table->dropIndex('comments_user_id_foreign');
        });

        Schema::table('craft_users', static function (Blueprint $table) {
            $table->dropForeign('craft_users_craft_id_foreign');
            $table->dropForeign('craft_users_user_id_foreign');

            $table->dropIndex('craft_users_craft_id_foreign');
            $table->dropIndex('craft_users_user_id_foreign');
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->dropForeign('department_project_department_id_foreign');
            $table->dropForeign('department_project_project_id_foreign');

            $table->dropIndex('department_project_department_id_foreign');
            $table->dropIndex('department_project_project_id_foreign');
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->integer('project_id')->change();
            $table->integer('department_id')->change();
        });

        Schema::table('event_comments', static function (Blueprint $table) {
            $table->dropForeign('event_comments_event_id_foreign');
            $table->dropForeign('event_comments_user_id_foreign');

            $table->dropIndex('event_comments_event_id_foreign');
            $table->dropIndex('event_comments_user_id_foreign');
        });

        Schema::table('event_type_filter', static function (Blueprint $table) {
            $table->dropForeign('event_type_filter_event_type_id_foreign');
            $table->dropForeign('event_type_filter_filter_id_foreign');

            $table->dropIndex('event_type_filter_event_type_id_foreign');
            $table->dropIndex('event_type_filter_filter_id_foreign');
        });

        Schema::table('event_type_filter', static function (Blueprint $table) {
            $table->integer('event_type_id')->change();
            $table->integer('filter_id')->change();
        });

        Schema::table('event_type_shift_filter', static function (Blueprint $table) {
            $table->dropForeign('event_type_shift_filter_event_type_id_foreign');
            $table->dropForeign('event_type_shift_filter_shift_filter_id_foreign');

            $table->dropIndex('event_type_shift_filter_event_type_id_foreign');
            $table->dropIndex('event_type_shift_filter_shift_filter_id_foreign');
        });

        Schema::table('event_type_shift_filter', static function (Blueprint $table) {
            $table->integer('event_type_id')->change();
            $table->integer('shift_filter_id')->change();
        });

        Schema::table('contracts', static function (Blueprint $table) {
            $table->dropForeign('contracts_project_id_foreign');
            $table->dropForeign('contracts_currency_id_foreign');
            $table->dropForeign('contracts_contract_type_id_foreign');
            $table->dropForeign('contracts_company_type_id_foreign');

            $table->dropIndex('contracts_project_id_foreign');
            $table->dropIndex('contracts_currency_id_foreign');
            $table->dropIndex('contracts_contract_type_id_foreign');
            $table->dropIndex('contracts_company_type_id_foreign');
        });

        Schema::table('contracts', static function (Blueprint $table) {
            $table->string('currency_id')->change();
            $table->string('contract_type_id')->change();
            $table->string('company_type_id')->change();
        });

        Schema::table('rooms', static function (Blueprint $table) {
            $table->dropForeign('rooms_area_id_foreign');
            $table->dropForeign('rooms_user_id_foreign');

            $table->dropIndex('rooms_area_id_foreign');
            $table->dropIndex('rooms_user_id_foreign');
        });

        Schema::table('shifts', static function (Blueprint $table) {
            $table->dropForeign('shifts_craft_id_foreign');
            $table->dropForeign('shifts_event_id_foreign');

            $table->dropIndex('shifts_craft_id_foreign');
            $table->dropIndex('shifts_event_id_foreign');
        });

        Schema::table('events', static function (Blueprint $t) {
            $t->dropForeign('events_event_type_id_foreign');
            $t->dropForeign('events_declined_room_id_foreign');
            $t->dropForeign('events_room_id_foreign');
            $t->dropForeign('events_user_id_foreign');
            $t->dropForeign('events_project_id_foreign');
            $t->dropForeign('events_series_id_foreign');

            $t->dropIndex('events_start_time_index');
            $t->dropIndex('events_end_time_index');
            $t->dropIndex('events_event_type_id_foreign');
            $t->dropIndex('events_declined_room_id_foreign');
            $t->dropIndex('events_room_id_foreign');
            $t->dropIndex('events_user_id_foreign');
            $t->dropIndex('events_project_id_foreign');
            $t->dropIndex('events_series_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
