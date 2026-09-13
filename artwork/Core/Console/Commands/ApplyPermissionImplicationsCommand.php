<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Services\PermissionImplicationService;
use Illuminate\Console\Command;

/**
 * Bestandsumgebungen: das stärkste gesetzte Recht einer Stufenleiter setzt die kleineren Stufen
 * bei allen Personen, Rollen und Presets. Idempotent, läuft in artwork:update.
 */
class ApplyPermissionImplicationsCommand extends Command
{
    protected $signature = 'artwork:permissions:apply-implications';
    protected $description = 'Ergänzt implizierte Rechte (Stufenleiter) bei Personen, Rollen und Presets';

    public function handle(PermissionImplicationService $service): int
    {
        $result = $service->applyToAll();
        $this->info(sprintf(
            'Implikationen angewendet: %d Personen, %d Rollen, %d Presets ergänzt.',
            $result['users'],
            $result['roles'],
            $result['presets']
        ));

        return self::SUCCESS;
    }
}
