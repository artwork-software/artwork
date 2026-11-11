<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Die Grenzen, mit denen gerechnet wurde (Echo der Anfrage plus `contract_rules`: ob
 * Vertragsregeln geprueft wurden — nur bei Nutzer:innen mit gueltigem Vertrag).
 */
class AiShiftLimitsData extends Data
{
    /**
     * @param string[] $exclude_weeks
     * @param string[] $exclude_days
     */
    public function __construct(
        public ?int $max_minutes_per_week,
        public ?int $max_minutes_per_month,
        public ?int $max_minutes_total,
        public string $fill,
        public array $exclude_weeks,
        public array $exclude_days,
        public ?int $craft_id,
        public bool $contract_rules,
    ) {
    }
}
