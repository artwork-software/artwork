<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die täglichen model:prune-Läufe filtern api_log und webhook_deliveries über created_at.
 * Ohne Index ist das ein Full-Table-Scan pro Tag — auf api_log (eine Zeile pro API-Request)
 * wird das schnell teuer; der zusammengesetzte (status, created_at)-Index auf
 * webhook_deliveries hilft der orWhere-Klausel des Prunes nicht.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['api_log', 'webhook_deliveries'] as $table) {
            if (!Schema::hasTable($table) || Schema::hasIndex($table, $table . '_created_at_index')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->index('created_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['api_log', 'webhook_deliveries'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasIndex($table, $table . '_created_at_index')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->dropIndex(['created_at']);
            });
        }
    }
};
