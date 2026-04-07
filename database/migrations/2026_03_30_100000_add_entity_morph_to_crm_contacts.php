<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table): void {
            $table->nullableMorphs('entity');
        });

        $this->backfill('users', \Artwork\Modules\User\Models\User::class);
        $this->backfill('freelancers', \Artwork\Modules\Freelancer\Models\Freelancer::class);
        $this->backfill('service_providers', \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class);

        if (Schema::hasColumn('manufacturers', 'crm_contact_id')) {
            $this->backfill('manufacturers', \Artwork\Modules\Manufacturer\Models\Manufacturer::class);
        }

        if (Schema::hasColumn('accommodations', 'crm_contact_id')) {
            $this->backfill('accommodations', \Artwork\Modules\Accommodation\Models\Accommodation::class);
        }

        if (Schema::hasColumn('artists', 'crm_contact_id')) {
            $this->backfill('artists', \Artwork\Modules\ArtistResidency\Models\Artist::class);
        }
    }

    private function backfill(string $table, string $morphClass): void
    {
        DB::table($table)
            ->whereNotNull('crm_contact_id')
            ->orderBy('id')
            ->each(function ($row) use ($morphClass) {
                DB::table('crm_contacts')
                    ->where('id', $row->crm_contact_id)
                    ->update([
                        'entity_type' => $morphClass,
                        'entity_id' => $row->id,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table): void {
            $table->dropMorphs('entity');
        });
    }
};
