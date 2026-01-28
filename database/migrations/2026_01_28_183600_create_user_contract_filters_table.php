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
        Schema::create('user_contract_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('ksk_liable')->default(false);
            $table->boolean('foreign_tax')->default(false);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('legal_form_ids')->nullable();
            $table->json('contract_type_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contract_filters');
    }
};
