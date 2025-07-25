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
        Schema::create('shift_commit_workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->foreignId('declined_by_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('status')
                ->default('pending');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_commit_workflow_requests');
    }
};
