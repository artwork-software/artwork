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
        Schema::create('money_source_reminders', function (Blueprint $table): void {
            $table->id();
            //cascade on delete so when corresponding money source model is deleted the reminders will also be deleted
            $table->foreignId('money_source_id')->constrained('money_sources')->cascadeOnDelete();
            $table->enum('type', ['expiration', 'threshold']);
            $table->integer('value');
            $table->boolean('notification_created')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('money_source_reminders');
    }
};
