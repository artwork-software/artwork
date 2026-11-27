<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committed_shift_changes', function (Blueprint $table): void {
            $table->boolean('is_short_notice')->default(false)->after('changed_at');
            $table->string('short_notice_reason', 40)->nullable()->after('is_short_notice');
            $table->string('short_notice_note', 500)->nullable()->after('short_notice_reason');
            $table->index(['is_short_notice', 'changed_at'], 'csc_short_notice_changed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('committed_shift_changes', function (Blueprint $table): void {
            $table->dropIndex('csc_short_notice_changed_at_index');
            $table->dropColumn(['is_short_notice', 'short_notice_reason', 'short_notice_note']);
        });
    }
};
