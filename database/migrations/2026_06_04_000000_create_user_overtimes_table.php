<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_overtimes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->integer('minutes'); // overtime minutes accrued that day
            $table->integer('remaining_minutes'); // minutes still open (not yet offset)
            $table->date('deadline');
            $table->string('status', 20)->default('open'); // open | compensated | payable | paid_out
            $table->foreignId('paid_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_out_at')->nullable();
            $table->text('payout_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_overtimes');
    }
};
