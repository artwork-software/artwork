<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_histories');
    }

    public function down(): void
    {
        Schema::create('project_histories', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('project_id');
            $table->bigInteger('user_id');
            $table->longText('description');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
