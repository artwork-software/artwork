<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wer einen Eintrag in den Papierkorb gelegt hat. Eine Zeile je Eintrag, solange er im Papierkorb liegt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trash_records', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trash_records');
    }
};
