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
        Schema::create('sage_api_settings', function (Blueprint $table): void {
            $table->id();
            $table->longText('host');
            $table->longText('endpoint');
            $table->longText('user');
            $table->longText('password');
            $table->date('bookingDate')->nullable();
            $table->string('fetchTime')->nullable();
            $table->boolean('enabled');
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
        Schema::dropIfExists('sage_api_settings');
    }
};
