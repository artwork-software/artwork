<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DP-18 Stufe 2: Historie manueller Überstunden-Auszahlungen (Doku). Die eigentliche
     * Saldo-Reduktion erfolgt zusätzlich als negative Zeitkorrektur-Buchung.
     */
    public function up(): void
    {
        Schema::create('overtime_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('minutes'); // ausgezahlte Minuten (positiv)
            $table->date('payout_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'payout_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_payouts');
    }
};
