<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_budget_account_display_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('show_number')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_budget_account_display_settings');
    }
};
