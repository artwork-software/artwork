<?php

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Artwork-Tickets: die Verbindung zum Ticket-Haus und alles, was artwork über den Verkauf seiner Termine weiß.
 * Ohne Verbindung bleiben die Tabellen leer und die Terminarten verkaufen nichts.
 */
return new class extends Migration
{
    private const PERMISSIONS = [PermissionEnum::TICKETING_MANAGE, PermissionEnum::TICKETING_MOVE_ON_SALE];

    public function up(): void
    {
        // Die Verbindung dieser Instanz zu ihrem Haus in tickets — es gibt höchstens eine.
        Schema::create('ticketing_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('tickets_url');
            $table->string('organization_id');
            $table->string('organization_slug');
            $table->string('dashboard_url');
            // Verschlüsselt über den Model-Cast; Zugriffe laufen über das Model.
            $table->text('api_key');
            // Passport-Client (client_credentials), mit dem tickets sich hier Tokens holt. String, weil
            // oauth_clients je nach Installation numerische oder UUID-Schlüssel trägt.
            $table->string('oauth_client_id', 36);
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Stand des Kundenabgleichs. Läuft einer, halten started_at und cursor die Stelle, an der ein
            // abgebrochener Lauf weitermacht; synced_at ist der Beginn des letzten vollständigen Laufs.
            $table->timestamp('customers_synced_at')->nullable();
            $table->timestamp('customers_sync_started_at')->nullable();
            $table->string('customers_sync_cursor', 64)->nullable();
            $table->text('customers_sync_error')->nullable();
            $table->timestamps();
        });

        // Welcher artwork-Raum welcher Spielstätte in tickets entspricht.
        Schema::create('ticketing_room_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->unique()->constrained('rooms')->cascadeOnDelete();
            $table->uuid('venue_id');
            $table->timestamps();
        });

        // Nur Termine solcher Terminarten tauchen in der Ticketing-Komponente eines Projekts auf
        // und können zum Verkauf in Artwork-Tickets freigegeben werden.
        Schema::table('event_types', function (Blueprint $table): void {
            $table->boolean('relevant_for_ticketing')->default(false)->after('relevant_for_project_period');
        });

        // Welche Preisklassen ein Termin im Ticketshop verkauft, mit Plätzen und Preis, und ob er dort
        // schon verkauft wird. Ohne Zeile gelten die Preisklassen der Spielstätte; erst die Freigabe
        // legt den Termin in Artwork-Tickets an und merkt sich dessen ID. description ersetzt im Shop den
        // Text der Produktion, reductions hält nur, worin der Termin von ihren Ermäßigungen abweicht.
        Schema::create('ticketing_event_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->json('classes');
            $table->text('description')->nullable();
            $table->json('reductions')->nullable();
            $table->string('state', 20)->default('draft');
            $table->uuid('tickets_date_id')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Warum der letzte Abgleich nach einer Kalenderänderung in tickets scheiterte; leer heißt auf Stand.
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        // Wie ein Projekt im Ticketshop auftritt: Anzeigename, Text, Bild, gewährte Ermäßigungen.
        // production_id entsteht mit der ersten Freigabe eines Termins; bis dahin ist die Zeile ein Entwurf.
        Schema::create('ticketing_productions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->uuid('production_id')->nullable();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->json('reduction_type_ids')->nullable();
            $table->string('hero_path')->nullable();
            $table->timestamp('hero_synced_at')->nullable();
            $table->timestamps();
        });

        // Weitere Bilder der Produktion neben dem Hauptbild, in Reihenfolge des Hochladens.
        // remote_id ist die Kennung in tickets; null, solange das Bild dort noch nicht liegt.
        Schema::create('ticketing_production_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticketing_production_id')->constrained('ticketing_productions')->cascadeOnDelete();
            $table->string('path');
            $table->uuid('remote_id')->nullable();
            $table->timestamps();
        });

        // Welcher CRM-Kontakt zu welchem Konto in Artwork-Tickets gehört — der Schlüssel des Abgleichs.
        Schema::create('ticketing_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_id', 64)->unique();
            $table->foreignId('crm_contact_id')->unique()->constrained('crm_contacts')->cascadeOnDelete();
            $table->timestamps();
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = collect((new BaseDataProvider())->getPermissions())->keyBy('name');
        foreach (self::PERMISSIONS as $permission) {
            $definition = $definitions->get($permission->value);

            if ($definition !== null) {
                Permission::firstOrCreate(['name' => $definition['name']], $definition);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', array_map(static fn (PermissionEnum $permission): string => $permission->value, self::PERMISSIONS))
            ->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Schema::dropIfExists('ticketing_customers');
        Schema::dropIfExists('ticketing_production_images');
        Schema::dropIfExists('ticketing_productions');
        Schema::dropIfExists('ticketing_event_releases');
        Schema::table('event_types', function (Blueprint $table): void {
            $table->dropColumn('relevant_for_ticketing');
        });
        Schema::dropIfExists('ticketing_room_links');
        Schema::dropIfExists('ticketing_connections');
    }
};
