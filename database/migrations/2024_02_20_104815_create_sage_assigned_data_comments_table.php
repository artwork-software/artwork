<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sage_assigned_data_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->references('id')
                ->nullOnDelete();
            $table->foreignId('sage_assigned_data_id')
                ->constrained('sage_assigned_data')
                ->references('id')
                ->cascadeOnDelete();
            $table->longText('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sage_assigned_data_comments');
    }
};
