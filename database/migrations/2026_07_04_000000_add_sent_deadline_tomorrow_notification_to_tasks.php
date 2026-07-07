<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Separate flag for the "deadline tomorrow" reminder so it is sent exactly
     * once without suppressing the later "deadline over" notification
     * (sent_deadline_notification keeps tracking the "over" case).
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->boolean('sent_deadline_tomorrow_notification')
                ->default(false)
                ->after('sent_deadline_notification');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('sent_deadline_tomorrow_notification');
        });
    }
};
