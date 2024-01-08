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
        Schema::create('task_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->boolean('done')->default(false);
            $table->unsignedBigInteger('checklist_template_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
