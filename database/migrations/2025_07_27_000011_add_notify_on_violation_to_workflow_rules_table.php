<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->boolean('notify_on_violation')->default(false)->after('configuration');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropColumn('notify_on_violation');
        });
    }
};