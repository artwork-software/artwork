<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * notification_settings hatte außer dem Primärschlüssel keinen Index: jeder Versand las die
 * Einstellung per Full-Scan, und doppelte Zeilen je Person/Typ waren möglich. Dubletten (ältere
 * Zeile behalten) und Zeilen gelöschter Konten werden vorher entfernt.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DELETE newer FROM notification_settings newer
             JOIN notification_settings older
               ON older.user_id = newer.user_id AND older.type = newer.type AND older.id < newer.id'
        );
        DB::table('notification_settings')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('users')->whereColumn('users.id', 'notification_settings.user_id');
            })
            ->delete();

        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->unique(['user_id', 'type']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'type']);
        });
    }
};
