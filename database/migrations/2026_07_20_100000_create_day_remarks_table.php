<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_remarks', function (Blueprint $table): void {
            $table->id();
            // Instanzweite Bemerkung pro Kalendertag — identischer Inhalt in
            // Kalender, Planungskalender und Dienstplan (Keying über das Datum).
            $table->date('date')->unique();
            $table->text('remark');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_remarks');
    }
};
