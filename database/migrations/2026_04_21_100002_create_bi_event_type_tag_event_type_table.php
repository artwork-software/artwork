<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bi_event_type_tag_event_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_type_id')->constrained('event_types')->cascadeOnDelete();
            $table->foreignId('bi_event_type_tag_id')->constrained('bi_event_type_tags')->cascadeOnDelete();
            $table->unique(['event_type_id', 'bi_event_type_tag_id'], 'bi_ett_et_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_event_type_tag_event_type');
    }
};
