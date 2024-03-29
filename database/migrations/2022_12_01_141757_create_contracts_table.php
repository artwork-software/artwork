<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string("name");
            $table->string('basename');
            $table->string("contract_partner");
            $table->integer('amount');
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users');
            $table->unsignedBigInteger('project_id');
            $table->string('description')->nullable();
            $table->string('contract_type_id')->nullable();
            $table->string('company_type_id')->nullable();
            $table->string('currency_id')->nullable();
            $table->boolean('ksk_liable')->default(false)->nullable();
            $table->boolean('resident_abroad')->default(false)->nullable();
            $table->boolean('is_freed')->default(false)->nullable();
            $table->boolean('has_power_of_attorney')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
