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
        Schema::create('event_verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifier'); // z.B. User, Freelancer, etc.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_verifications');
    }
};
