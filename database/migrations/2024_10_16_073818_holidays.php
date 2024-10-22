<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->integer('rota')->nullable();
            $table->string('country')->nullable();
            $table->string('remote_identifier')->nullable();
            $table->boolean('from_api')->default(false);
            $table->timestamps();
        });

        Schema::create('subdivisions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('country_code');
            $table->timestamps();
        });

        Schema::create('holidays_subdivisions', function (Blueprint $table): void {
            $table->unsignedBigInteger('holiday_id');
            $table->unsignedBigInteger('subdivision_id');
        });

        Schema::table('holidays_subdivisions', function (Blueprint $table): void {
            $table->foreign('holiday_id')->references('id')->on('holidays');
            $table->foreign('subdivision_id')->references('id')->on('subdivisions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('holidays_subdivisions');
        Schema::drop('holidays');
        Schema::drop('subdivisions');
    }
};
