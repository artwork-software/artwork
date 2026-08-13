<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Das API-Zugriffslog referenziert Tokens künftig direkt über die Passport-Token-ID statt über die
 * Zwischentabelle api_access_tokens (die im Folge-Migrationsschritt entfällt).
 *
 * Dabei fallen zwei Datenschutzprobleme weg: api_key hielt den Bearer-Token bei jedem Request erneut
 * im Klartext, payload den vollständigen ungefilterten Request-Body. Ersatzlos gestrichen — ab der
 * Ticketing-API würden dort personenbezogene Käuferdaten landen.
 *
 * Neu hinzu kommen response_status und duration_ms, damit das Log überhaupt aussagekräftig ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_log', function (Blueprint $table): void {
            $table->string('passport_token_id', 100)->nullable()->after('id')->index();
            $table->unsignedSmallInteger('response_status')->nullable()->after('user_agent');
            $table->unsignedInteger('duration_ms')->nullable()->after('response_status');
        });

        if (Schema::hasTable('api_access_tokens')) {
            DB::statement(
                'UPDATE api_log
                 INNER JOIN api_access_tokens ON api_access_tokens.id = api_log.token_id
                 SET api_log.passport_token_id = api_access_tokens.passport_token_id'
            );
        }

        Schema::table('api_log', function (Blueprint $table): void {
            if (Schema::hasIndex('api_log', 'api_log_token_id_index')) {
                $table->dropIndex('api_log_token_id_index');
            }

            $table->dropColumn(['token_id', 'api_key', 'payload']);
        });
    }

    public function down(): void
    {
        Schema::table('api_log', function (Blueprint $table): void {
            // Bewusst nullable: Die Klartextwerte sind unwiederbringlich und sollen es auch bleiben.
            $table->unsignedBigInteger('token_id')->nullable()->after('id')->index();
            $table->text('api_key')->nullable()->after('token_id');
            $table->longText('payload')->nullable()->after('ip');
        });

        Schema::table('api_log', function (Blueprint $table): void {
            if (Schema::hasIndex('api_log', 'api_log_passport_token_id_index')) {
                $table->dropIndex('api_log_passport_token_id_index');
            }

            $table->dropColumn(['passport_token_id', 'response_status', 'duration_ms']);
        });
    }
};
