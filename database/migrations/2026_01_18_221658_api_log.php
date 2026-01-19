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
        Schema::create('api_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id')->index();
            $table->text('api_key');
            $table->string('url');
            $table->string('method');
            $table->string('ip');
            $table->longText('payload')->nullable();
            $table->string('user_agent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_log');
    }
};
