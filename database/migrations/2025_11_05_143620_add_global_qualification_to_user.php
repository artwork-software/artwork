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
        Schema::create('user_global_qualifications', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('global_qualification_id');
            $table->timestamps();

            // KURZER UNIQUE-INDEX
            $table->unique(['user_id', 'global_qualification_id'], 'ugq_user_qual_uq');

            // KURZE FK-NAMEN (optional, aber sicher bei langen Tabellennamen)
            $table->foreign('user_id', 'fk_ugq_user')
                ->references('id')->on('users')->onDelete('cascade');

            $table->foreign('global_qualification_id', 'fk_ugq_qual')
                ->references('id')->on('global_qualifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_global_qualifications');
    }
};
