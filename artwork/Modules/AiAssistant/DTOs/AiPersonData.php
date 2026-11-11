<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine planbare Person (ai-shift-planning): Nutzer:in, Freelancer oder Dienstleister.
 *
 * `type` ist `user`, `freelancer` oder `service_provider` und bildet zusammen mit `id` den
 * Schluessel, den `workload`, `shift-plan/suggest` und `shift-assignments` erwarten.
 * `has_contract` ist nur bei Nutzer:innen mit heute gueltigem Arbeitsvertrag wahr — nur fuer sie
 * gelten die Vertragsregeln.
 */
class AiPersonData extends Data
{
    /**
     * @param AiRefData[] $crafts
     * @param AiRefData[] $qualifications
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public array $crafts,
        public array $qualifications,
        public bool $has_contract,
    ) {
    }
}
