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
        Schema::create('room_user', function (Blueprint $table): void {
            $table->id();
            $table->integer('room_id');
            $table->integer('user_id');
            $table->boolean('is_admin')->default(false);
            $table->boolean('can_request')->default(false);
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
        Schema::dropIfExists('room_user');
    }
};
