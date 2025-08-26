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
        Schema::table('users', function (Blueprint $table) {
            // chat_popup_position
            $table->string('chat_popup_position', 20)
                ->default('bottom-right')
                ->after('use_chat')
                ->comment('Position des Chat-Popups (bottom-right, bottom-left, top-right, top-left)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Entfernen der Spalte chat_popup_position
            $table->dropColumn('chat_popup_position');
            //
        });
    }
};
