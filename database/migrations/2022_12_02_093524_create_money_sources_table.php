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
        Schema::create('money_sources', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('creator_id');
            $table->string('name');
            $table->float('amount', 25, 2)->default(0.00);
            $table->string('source_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('users')->nullable();
            $table->json('pinned_by_users')->nullable();
            $table->bigInteger('group_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_group')->default(false);
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
        Schema::dropIfExists('money_sources');
    }
};
