<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Projekt mit Budget, aber ohne Spalte fuer den gefragten Zeitraum; `columns` nennt die vorhandenen
 * Wertspalten („2025 A"), damit der Assistent erklaeren kann, warum es fehlt.
 */
class AiBudgetRankingMissingColumnData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        /** @var list<string> */
        public array $columns,
    ) {
    }
}
