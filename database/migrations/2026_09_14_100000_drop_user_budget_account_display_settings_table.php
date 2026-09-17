<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Der Nutzer-Schalter „Nummer anzeigen / Name anzeigen" im Budget entfällt:
        // KTO/KST werden bei aktiver Kontenverwaltung immer als „Nummer – Name" angezeigt (Entscheidung 14.09.2026).
        Schema::dropIfExists('user_budget_account_display_settings');
    }

    public function down(): void
    {
        if (Schema::hasTable('user_budget_account_display_settings')) {
            return;
        }

        Schema::create('user_budget_account_display_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('show_number')->default(true);
        });
    }
};
