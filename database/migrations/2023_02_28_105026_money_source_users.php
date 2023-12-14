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
        Schema::create('money_source_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('money_source_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('competent')->default(false);
            $table->boolean('write_access')->default(false);
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
        Schema::dropIfExists('money_source_users');
    }
};
