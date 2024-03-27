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
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->boolean('done');
            $table->timestamp('deadline')->nullable()->default(null);
            $table->timestamp('done_at')->nullable()->default(null);
            $table->integer('order');
            $table->unsignedBigInteger('checklist_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->default(null);
            $table->unsignedBigInteger('contract_id')->nullable()->default(null);
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
        Schema::dropIfExists('tasks');
    }
};
