<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_sum_details', function (Blueprint $table) {
            $table->index('column_id');
        });

        Schema::table('main_position_details', function (Blueprint $table) {
            $table->index('main_position_id');
            $table->index('column_id');
        });

        Schema::table('main_position_verifieds', function (Blueprint $table) {
            $table->index('main_position_id');
            $table->index('requested_by');
        });

        Schema::table('main_positions', function (Blueprint $table) {
            $table->index('table_id');
        });

        Schema::table('sub_position_verifieds', function (Blueprint $table) {
            $table->index('sub_position_id');
            $table->index('requested_by');
        });

        Schema::table('sub_positions', function (Blueprint $table) {
            $table->index('main_position_id');
        });

        Schema::table('subposition_sum_details', function (Blueprint $table) {
            $table->index('sub_position_id');
            $table->index('column_id');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->index('project_id');
        });

        Schema::table('money_source_files', function (Blueprint $table) {
            $table->index('money_source_id');
        });

        Schema::table('money_source_project', function (Blueprint $table) {
            $table->index('money_source_id');
            $table->index('project_id');
        });

        Schema::table('money_source_task_user', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('user_id');
        });

        Schema::table('money_source_tasks', function (Blueprint $table) {
            $table->index('money_source_id');
            $table->index('creator');
        });

        Schema::table('money_source_user_pinned', function (Blueprint $table) {
            $table->index('money_source_id');
            $table->index('user_id');
        });

        Schema::table('money_source_users', function (Blueprint $table) {
            $table->index('money_source_id');
            $table->index('user_id');
        });

        Schema::table('money_sources', function (Blueprint $table) {
            $table->index('creator_id');
            $table->index('group_id');
        });

        Schema::table('sum_money_sources', function (Blueprint $table) {
            $table->index('money_source_id');
        });

        Schema::table('sage_not_assigned_data', function (Blueprint $table) {
            $table->index('parent_booking_id');
            $table->index('sage_id');
        });

        Schema::table('sage_assigned_data', function (Blueprint $table) {
            $table->index('parent_booking_id');
            $table->index('sage_id');
        });

        Schema::table('columns', function (Blueprint $table) {
            $table->index('linked_first_column');
            $table->index('linked_second_column');
        });

        Schema::table('cell_comments', function (Blueprint $table) {
            $table->index('column_cell_id');
            $table->index('user_id');
        });

        // comments (contract/project/money source comments)
        Schema::table('comments', function (Blueprint $table) {
            $table->index('contract_id');
            $table->index('money_source_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_sum_details', function (Blueprint $table) {
            $table->dropIndex(['column_id']);
        });

        Schema::table('main_position_details', function (Blueprint $table) {
            $table->dropIndex(['main_position_id']);
            $table->dropIndex(['column_id']);
        });

        Schema::table('main_position_verifieds', function (Blueprint $table) {
            $table->dropIndex(['main_position_id']);
            $table->dropIndex(['requested_by']);
        });

        Schema::table('main_positions', function (Blueprint $table) {
            $table->dropIndex(['table_id']);
        });

        Schema::table('sub_position_verifieds', function (Blueprint $table) {
            $table->dropIndex(['sub_position_id']);
            $table->dropIndex(['requested_by']);
        });

        Schema::table('sub_positions', function (Blueprint $table) {
            $table->dropIndex(['main_position_id']);
        });

        Schema::table('subposition_sum_details', function (Blueprint $table) {
            $table->dropIndex(['sub_position_id']);
            $table->dropIndex(['column_id']);
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
        });

        Schema::table('money_source_files', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
        });

        Schema::table('money_source_project', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
            $table->dropIndex(['project_id']);
        });

        Schema::table('money_source_task_user', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('money_source_tasks', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
            $table->dropIndex(['creator']);
        });

        Schema::table('money_source_user_pinned', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('money_source_users', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('money_sources', function (Blueprint $table) {
            $table->dropIndex(['creator_id']);
            $table->dropIndex(['group_id']);
        });

        Schema::table('sum_money_sources', function (Blueprint $table) {
            $table->dropIndex(['money_source_id']);
        });

        Schema::table('sage_not_assigned_data', function (Blueprint $table) {
            $table->dropIndex(['parent_booking_id']);
            $table->dropIndex(['sage_id']);
        });

        Schema::table('sage_assigned_data', function (Blueprint $table) {
            $table->dropIndex(['parent_booking_id']);
            $table->dropIndex(['sage_id']);
        });

        Schema::table('columns', function (Blueprint $table) {
            $table->dropIndex(['linked_first_column']);
            $table->dropIndex(['linked_second_column']);
        });

        Schema::table('cell_comments', function (Blueprint $table) {
            $table->dropIndex(['column_cell_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['contract_id']);
            $table->dropIndex(['money_source_file_id']);
        });
    }
};
