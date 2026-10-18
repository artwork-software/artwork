<?php

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gespiegelte CRM-Kontakte gelöschter Nutzer*innen, Freelancer und Dienstleister blieben bisher
 * samt Eigenschaftswerten stehen und waren im CRM (schreibgeschützt) nicht löschbar. Sie werden
 * jetzt mit der Entität gelöscht (DeletesMirroredCrmContact); hier werden die Altlasten entfernt,
 * ebenso ein per artwork:update angelegter Kontakt des Platzhalters „Deleted user“.
 * crm_property_values, Projekt-Pivots und externe Zugänge hängen per ON DELETE CASCADE daran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_contacts')) {
            return;
        }

        $mirroredEntityTables = [
            User::class => 'users',
            Freelancer::class => 'freelancers',
            ServiceProvider::class => 'service_providers',
        ];

        foreach ($mirroredEntityTables as $entityType => $table) {
            DB::table('crm_contacts')
                ->where('entity_type', $entityType)
                ->whereNotExists(function (Builder $query) use ($table): void {
                    $query->selectRaw('1')
                        ->from($table)
                        ->whereColumn($table . '.id', 'crm_contacts.entity_id');
                })
                ->delete();
        }

        $placeholderUserId = DB::table('users')
            ->where('email', config('artwork.deleted_user_email', 'deleted-user@artwork.local'))
            ->value('id');

        if ($placeholderUserId !== null) {
            DB::table('crm_contacts')
                ->where('entity_type', User::class)
                ->where('entity_id', $placeholderUserId)
                ->delete();
        }
    }

    public function down(): void
    {
        // Datenbereinigung, nicht umkehrbar
    }
};
