<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_shift_list_view_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('show_qualifications')->default(false);
            $table->boolean('shift_notes')->default(false);
            $table->boolean('show_shift_group_tag')->default(false);
            $table->boolean('show_fully_staffed_shifts')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shift_list_view_settings');
    }
};
