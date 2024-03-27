<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('name_de')->nullable();
            $table->string('group')->nullable();
            $table->longText('tooltipText')->nullable();
            $table->boolean('checked')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropColumn('name_de');
            $table->dropColumn('group');
            $table->dropColumn('tooltipText');
            $table->dropColumn('checked');
        });
    }
};
