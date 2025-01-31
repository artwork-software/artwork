<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_property_sub_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_property_id')->constrained('event_properties')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_event_property');
    }
};
