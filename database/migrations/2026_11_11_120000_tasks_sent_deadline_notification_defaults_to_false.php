<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Der Merker für die Erinnerung nach Fristüberschreitung stand standardmäßig auf "verschickt". Jede neu
 * angelegte Aufgabe galt damit als bereits erinnert, und die Erinnerung nach Überschreitung blieb aus,
 * solange niemand die Frist nachträglich änderte.
 *
 * Offene Aufgaben, deren Frist noch in der Zukunft liegt, können noch keine Überschreitungs-Erinnerung
 * bekommen haben; für sie wird der Merker zurückgesetzt. Bereits überschrittene Aufgaben bleiben, wie
 * sie sind, damit beim Einspielen keine Erinnerungen für alte Fristen nachträglich verschickt werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->boolean('sent_deadline_notification')->default(false)->change();
        });

        DB::table('tasks')
            ->where('done', false)
            ->whereNull('deleted_at')
            ->whereNotNull('deadline')
            ->where('deadline', '>', now())
            ->update(['sent_deadline_notification' => false]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->boolean('sent_deadline_notification')->default(true)->change();
        });
    }
};
