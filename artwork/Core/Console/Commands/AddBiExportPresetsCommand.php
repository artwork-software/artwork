<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Illuminate\Console\Command;

class AddBiExportPresetsCommand extends Command
{
    protected $signature = 'artwork:add-bi-export-presets';
    protected $description = 'Seed the default standardised BI export presets (idempotent)';

    /**
     * Default standardised funder/"Träger" export modelled on the HAU example file.
     * Only static columns are included; tenant-specific tag and custom-field columns
     * must be added per house.
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULT_PRESETS = [
        'Standardisierter Träger-Export' => [
            'project_name',
            'artists',
            'rooms',
            'areas',
            'project_state',
            'main_category',
            'first_event_date',
            'seats_capacity',
            'visitors',
            'avg_price',
            'revenue',
            'premiere_date',
            'production_type',
            'season_year',
        ],
        // Spiegelbild der "Interne Steuerung"-Tabelle im BI-Dashboard
        'Steuerungssicht' => [
            'project_name',
            'main_category',
            'visitors',
            'revenue',
            'occupancy_rate',
            'contract_count',
            'booking_count',
            'contracts_per_performance',
            'bookings_per_performance',
            'tasks_docs_per_production',
            'effort_score',
            'plan_visitors',
            'plan_revenue',
            'attainment',
        ],
    ];

    public function handle(): void
    {
        foreach (self::DEFAULT_PRESETS as $name => $columns) {
            if (BiExportPreset::query()->where('name', $name)->exists()) {
                $this->info('BI export preset "' . $name . '" already exists');
                continue;
            }

            BiExportPreset::create([
                'name' => $name,
                'columns' => $columns,
                'created_by' => null,
                'is_shared' => true,
            ]);

            $this->info('BI export preset "' . $name . '" added');
        }
    }
}
