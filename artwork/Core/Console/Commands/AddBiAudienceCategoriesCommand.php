<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Illuminate\Console\Command;

class AddBiAudienceCategoriesCommand extends Command
{
    protected $signature = 'artwork:add-bi-audience-categories';
    protected $description = 'Seed the default set of Business Intelligence audience categories (idempotent)';

    /**
     * @var array<int, array{name: string, pricing_type: string, position: int}>
     */
    private const DEFAULT_CATEGORIES = [
        ['name' => 'Vollzahler*innen', 'pricing_type' => 'full', 'position' => 0],
        ['name' => 'Ermäßigt', 'pricing_type' => 'reduced', 'position' => 1],
        ['name' => 'Freikarten', 'pricing_type' => 'free', 'position' => 2],
    ];

    public function handle(): void
    {
        // Nur beim allerersten Lauf seeden: sobald irgendeine Kategorie existiert (auch soft-deleted),
        // hat das Haus die Liste übernommen und gelöschte Defaults dürfen nicht wiederkommen.
        if (BiAudienceCategory::withTrashed()->exists()) {
            $this->info('BI audience categories already present, skipping seed');

            return;
        }

        foreach (self::DEFAULT_CATEGORIES as $category) {
            BiAudienceCategory::create($category);
            $this->info('BI audience category "' . $category['name'] . '" added');
        }
    }
}
