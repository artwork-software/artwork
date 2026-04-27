<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tabs', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('project_tabs', function (Blueprint $table) {
            $table->string('name', 30)->change();
        });
    }
};
