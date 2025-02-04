<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_print_layouts', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('columns_header')->default(1);
            $table->integer('columns_footer')->default(1);
            $table->integer('columns_body')->default(1);
            $table->integer('order');
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->references('id')->on('users');
            $table->string('permission')->default('allCanPrint');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_print_layouts');
    }
};
