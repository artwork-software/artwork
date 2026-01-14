<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tabs', function (Blueprint $table) {
            $table->boolean('visible_for_all')->default(true)->after('default');
        });

        Schema::create('project_tab_visible_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_tab_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['project_tab_id', 'user_id'], 'ptvu_tab_user_uq');
            $table->foreign('project_tab_id', 'ptvu_tab_fk')
                ->references('id')->on('project_tabs')->onDelete('cascade');
            $table->foreign('user_id', 'ptvu_user_fk')
                ->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('project_tab_visible_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_tab_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();
            $table->unique(['project_tab_id', 'department_id'], 'ptvd_tab_dept_uq');
            $table->foreign('project_tab_id', 'ptvd_tab_fk')
                ->references('id')->on('project_tabs')->onDelete('cascade');
            $table->foreign('department_id', 'ptvd_dept_fk')
                ->references('id')->on('departments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tab_visible_users');
        Schema::dropIfExists('project_tab_visible_departments');

        Schema::table('project_tabs', function (Blueprint $table) {
            $table->dropColumn('visible_for_all');
        });
    }
};
