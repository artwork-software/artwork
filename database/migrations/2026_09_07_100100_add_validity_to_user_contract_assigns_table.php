<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vertragszuweisung wird Historie: Ein Satz je Gültigkeitszeitraum (valid_from/valid_until).
     * Bestand bleibt unverändert – valid_from null bedeutet "offen ab Beginn", valid_until null
     * "offen bis auf Weiteres". Der heute gültige Satz ist weiterhin über User::contract() erreichbar.
     */
    private const INDEX = 'user_contract_assigns_user_id_valid_from_index';

    public function up(): void
    {
        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_contract_assigns', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('user_contract_id');
            }
            if (!Schema::hasColumn('user_contract_assigns', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }
        });

        if (!Schema::hasIndex('user_contract_assigns', self::INDEX)) {
            Schema::table('user_contract_assigns', function (Blueprint $table): void {
                $table->index(['user_id', 'valid_from'], self::INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('user_contract_assigns', self::INDEX)) {
            Schema::table('user_contract_assigns', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX);
            });
        }

        Schema::table('user_contract_assigns', function (Blueprint $table): void {
            if (Schema::hasColumn('user_contract_assigns', 'valid_until')) {
                $table->dropColumn('valid_until');
            }
            if (Schema::hasColumn('user_contract_assigns', 'valid_from')) {
                $table->dropColumn('valid_from');
            }
        });
    }
};
