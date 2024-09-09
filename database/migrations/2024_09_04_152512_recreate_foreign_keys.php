<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('adjoining_room_main_room', static function (Blueprint $table) {
            $table->unsignedBigInteger('adjoining_room_id')->change();
            $table->unsignedBigInteger('main_room_id')->change();
        });

        Schema::table('adjoining_room_main_room', static function (Blueprint $table) {
            $table->foreign('adjoining_room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnDelete();
            $table->foreign('main_room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnDelete();
        });

        Schema::table('availabilities', static function (Blueprint $table) {
            $table->unsignedBigInteger('series_id')->change();
        });

        Schema::table('availabilities_conflicts', static function (Blueprint $table) {
            $table->foreign('availability_id')
                ->references('id')
                ->on('availabilities')
                ->cascadeOnDelete();
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->cascadeOnDelete();
        });

        Schema::table('category_project', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->unsignedBigInteger('cell_id')->change();
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->foreign('cell_id')
                ->references('id')
                ->on('column_sub_position_row')
                ->cascadeOnDelete();
        });

        Schema::table('checklist_template_user', static function (Blueprint $table) {
            $table->foreign('checklist_template_id')
                ->references('id')
                ->on('checklist_templates')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('checklist_user', static function (Blueprint $table) {
            $table->foreign('checklist_id')
                ->references('id')
                ->on('checklists')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('checklist_templates', static function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('checklist_templates', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('checklists', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });

        Schema::table('column_sub_position_row', static function (Blueprint $table) {
            $table->unsignedBigInteger('column_id')->change();
            $table->unsignedBigInteger('sub_position_row_id')->change();
            $table->unsignedBigInteger('linked_money_source_id')->change();
        });

        Schema::table('column_sub_position_row', static function (Blueprint $table) {
            $table->foreign('column_id')
                ->references('id')
                ->on('columns')
                ->cascadeOnDelete();
            $table->foreign('sub_position_row_id')
                ->references('id')
                ->on('sub_position_rows')
                ->cascadeOnDelete();
            $table->foreign('linked_money_source_id')
                ->references('id')
                ->on('money_sources')
                ->cascadeOnDelete();
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->change();
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->foreign('table_id')
                ->references('id')
                ->on('tables')
                ->cascadeOnDelete();
            $table->foreign('locked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('comments', static function (Blueprint $table) {
            $table->text('text')->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('comments', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('project_file_id')
                ->references('id')
                ->on('contracts')
                ->nullOnDelete();
        });

        Schema::table('craft_users', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('craft_id')
                ->references('id')
                ->on('crafts')
                ->cascadeOnDelete();
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->change();
            $table->unsignedBigInteger('department_id')->change();
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();
        });

        Schema::table('event_comments', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();
        });

        Schema::table('event_type_filter', static function (Blueprint $table) {
            $table->unsignedBigInteger('event_type_id')->change();
            $table->unsignedBigInteger('filter_id')->change();
        });

        Schema::table('event_type_shift_filter', static function (Blueprint $table) {
            $table->unsignedBigInteger('event_type_id')->change();
            $table->unsignedBigInteger('shift_filter_id')->change();
        });

        Schema::table('event_type_filter', static function (Blueprint $table) {
            $table->foreign('event_type_id')
                ->references('id')
                ->on('event_types')
                ->cascadeOnDelete();
            $table->foreign('filter_id')
                ->references('id')
                ->on('filters')
                ->cascadeOnDelete();
        });

        Schema::table('event_type_shift_filter', static function (Blueprint $table) {
            $table->foreign('event_type_id')
                ->references('id')
                ->on('event_types')
                ->cascadeOnDelete();
            $table->foreign('shift_filter_id')
                ->references('id')
                ->on('shift_filters')
                ->cascadeOnDelete();
        });

        Schema::table('events', static function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('events', static function (Blueprint $table) {
            $table->foreign('event_type_id')
                ->references('id')
                ->on('event_types');
            $table->foreign('declined_room_id')
                ->references('id')
                ->on('rooms')
                ->nullOnDelete();
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->nullOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
            $table->foreign('series_id')
                ->references('id')
                ->on('series_events');
        });

        Schema::table('events', static function (Blueprint $table) {
            $table->index('start_time');
            $table->index('end_time');
        });

        Schema::table('contracts', static function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->change();
            $table->unsignedBigInteger('contract_type_id')->change();
            $table->unsignedBigInteger('company_type_id')->change();
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        Schema::table('contracts', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();

            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->restrictOnDelete();

            $table->foreign('contract_type_id')
                ->references('id')
                ->on('contract_types')
                ->restrictOnDelete();

            $table->foreign('company_type_id')
                ->references('id')
                ->on('company_types')
                ->restrictOnDelete();
        });

        Schema::table('rooms', static function (Blueprint $table) {
            $table->foreign('area_id')
                ->references('id')
                ->on('areas');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('shifts', static function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();
            $table->foreign('craft_id')
                ->references('id')
                ->on('crafts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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

        Schema::table('comments', static function (Blueprint $table) {
            $table->string('text')->change();
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
};
