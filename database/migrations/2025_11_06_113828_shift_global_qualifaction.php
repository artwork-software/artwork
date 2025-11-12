<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_global_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('global_qualification_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();

            // eindeutiger, kurzer Unique-Index
            $table->unique(['shift_id', 'global_qualification_id'], 'sgq_shift_qual_uq');

            // Eindeutige FK-Namen (nicht identisch zu anderen Tabellen!)
            $table->foreign('shift_id', 'fk_sgq_shift')
                ->references('id')->on('shifts')->onDelete('cascade');

            $table->foreign('global_qualification_id', 'fk_sgq_gq')
                ->references('id')->on('global_qualifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_global_qualifications');
    }
};
