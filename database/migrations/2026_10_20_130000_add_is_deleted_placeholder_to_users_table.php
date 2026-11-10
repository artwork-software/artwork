<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der Platzhalter „Gelöschte:r Nutzer:in“ (übernimmt Projekte, Termine usw. gelöschter Personen) wurde
     * bisher über die E-Mail deleted-user@artwork.local aufgelöst. Jedes Konto, das sich diese Adresse
     * gibt, hätte alles Weitere übernommen. Künftig zählt nur das Flag; markiert wird nur der echte,
     * vom System angelegte Platzhalter (Name „Deleted user“, ohne Rolle).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_deleted_placeholder')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_deleted_placeholder')->default(false)->index();
            });
        }

        DB::table('users')
            ->where('email', 'deleted-user@artwork.local')
            ->where('first_name', 'Deleted')
            ->where('last_name', 'user')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'like', '%User');
            })
            ->update(['is_deleted_placeholder' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_deleted_placeholder')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(['is_deleted_placeholder']);
                $table->dropColumn('is_deleted_placeholder');
            });
        }
    }
};
