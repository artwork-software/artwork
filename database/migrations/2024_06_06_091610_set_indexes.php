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
                ->cascadeOnUpdate();
            $table->foreign('main_room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnUpdate();
        });

        Schema::table('availabilities', static function (Blueprint $table) {
            $table->unsignedBigInteger('series_id')->change();
        });

        Schema::table('availabilities', static function (Blueprint $table) {
            $table->foreign('series_id')
                ->references('id')
                ->on('availability_series')
                ->cascadeOnUpdate();
        });

        Schema::table('availabilities_conflicts', static function (Blueprint $table) {
            $table->foreign('availability_id')
                ->references('id')
                ->on('availabilities')
                ->cascadeOnUpdate();
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->cascadeOnUpdate();
        });

        Schema::table('category_project', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnUpdate();

            $table->index(['project_id', 'category_id']);
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->unsignedBigInteger('cell_id')->change();
        });

        Schema::table('cell_calculations', static function (Blueprint $table) {
            $table->foreign('cell_id')
                ->references('id')
                ->on('column_sub_position_row')
                ->cascadeOnUpdate();
        });

        Schema::table('checklist_template_user', static function (Blueprint $table) {
            $table->foreign('checklist_template_id')
                ->references('id')
                ->on('checklist_templates')
                ->cascadeOnUpdate();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });

        Schema::table('checklist_user', static function (Blueprint $table) {
            $table->foreign('checklist_id')
                ->references('id')
                ->on('checklists')
                ->cascadeOnUpdate();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });

        Schema::table('checklist_templates', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });

        Schema::table('checklists', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();
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
                ->cascadeOnUpdate();
            $table->foreign('sub_position_row_id')
                ->references('id')
                ->on('sub_position_rows')
                ->cascadeOnUpdate();
            $table->foreign('linked_money_source_id')
                ->references('id')
                ->on('money_sources')
                ->cascadeOnUpdate();
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->change();
        });

        Schema::table('columns', static function (Blueprint $table) {
            $table->foreign('table_id')
                ->references('id')
                ->on('tables')
                ->cascadeOnUpdate();
            $table->foreign('locked_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });

        Schema::table('comments', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
            $table->foreign('project_file_id')
                ->references('id')
                ->on('contracts')
                ->cascadeOnUpdate();
            $table->text('text')->change();
        });

        Schema::table('craft_users', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
            $table->foreign('craft_id')
                ->references('id')
                ->on('crafts')
                ->cascadeOnUpdate();
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->change();
            $table->unsignedBigInteger('department_id')->change();
        });

        Schema::table('department_project', static function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnUpdate();
        });

        Schema::table('event_comments', static function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnUpdate();
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
                ->cascadeOnUpdate();
            $table->foreign('filter_id')
                ->references('id')
                ->on('filters')
                ->cascadeOnUpdate();
        });

        Schema::table('event_type_shift_filter', static function (Blueprint $table) {
            $table->foreign('event_type_id')
                ->references('id')
                ->on('event_types')
                ->cascadeOnUpdate();
            $table->foreign('shift_filter_id')
                ->references('id')
                ->on('shift_filters')
                ->cascadeOnUpdate();
        });

        Schema::table('events', static function (Blueprint $table) {
            $table->foreign('event_type_id')
                ->references('id')
                ->on('event_types')
                ->cascadeOnUpdate();
            $table->foreign('declined_room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnUpdate();
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnUpdate();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();
            $table->foreign('series_id')
                ->references('id')
                ->on('series_events')
                ->cascadeOnUpdate();
        });

        Schema::table('contracts', static function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->change();
            $table->unsignedBigInteger('contract_type_id')->change();
            $table->unsignedBigInteger('company_type_id')->change();
        });

        Schema::table('contracts', static function (Blueprint $table) {

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate();

            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('contract_type_id')
                ->references('id')
                ->on('contract_types')
                ->cascadeOnUpdate();

            $table->foreign('company_type_id')
                ->references('id')
                ->on('company_types')
                ->cascadeOnUpdate();
        });

        Schema::table('rooms', static function (Blueprint $table) {
            $table->foreign('area_id')
                ->references('id')
                ->on('areas')
                ->cascadeOnUpdate();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });

        Schema::table('shifts', static function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnUpdate();
            $table->foreign('craft_id')
                ->references('id')
                ->on('crafts')
                ->cascadeOnUpdate();
        });

        Schema::table('events', static function(Blueprint $t) {
            $t->index('start_time');
            $t->index('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
