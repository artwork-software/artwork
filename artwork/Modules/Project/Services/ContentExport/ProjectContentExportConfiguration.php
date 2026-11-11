<?php

namespace Artwork\Modules\Project\Services\ContentExport;

use Carbon\CarbonImmutable;

/**
 * Validierte Einstellungen eines Projektinhalte-Exports (Format, Spalten, Projektauswahl, Termin-Spalte).
 */
final readonly class ProjectContentExportConfiguration
{
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_DOCX = 'docx';

    /**
     * @param array<int, array{key: string, title: string|null, show_label: bool}> $columns
     * @param array<int, int> $projectStateIds
     * @param array<int, int> $categoryIds
     * @param array<int, int> $genreIds
     * @param array<int, int> $sectorIds
     * @param array<int, int> $eventTypeIds Merkmal: Projekte mit Terminen dieser Arten
     * @param array<int, int> $memberIds
     * @param array<int, int> $eventColumnTypeIds Terminarten der Termin-Spalte (leer = alle)
     */
    public function __construct(
        public string $format,
        public array $columns,
        public ?CarbonImmutable $dateStart = null,
        public ?CarbonImmutable $dateEnd = null,
        public ?int $projectGroupId = null,
        public array $projectStateIds = [],
        public array $categoryIds = [],
        public array $genreIds = [],
        public array $sectorIds = [],
        public array $eventTypeIds = [],
        public array $memberIds = [],
        public array $eventColumnTypeIds = [],
        public ?int $templateId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data validierte Request-Daten
     */
    public static function fromArray(array $data): self
    {
        $filters = (array) ($data['filters'] ?? []);
        $ids = static fn (mixed $values): array => array_values(array_unique(array_map(
            'intval',
            array_filter((array) ($values ?? []), 'is_numeric')
        )));
        $date = static fn (mixed $value): ?CarbonImmutable => is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : null;

        $dateStart = $date($filters['date_start'] ?? null)?->startOfDay();
        $dateEnd = $date($filters['date_end'] ?? null)?->endOfDay();

        return new self(
            format: (string) $data['format'],
            columns: array_values(array_map(static fn (array $column): array => [
                'key' => (string) $column['key'],
                'title' => isset($column['title']) && trim((string) $column['title']) !== ''
                    ? trim((string) $column['title'])
                    : null,
                'show_label' => (bool) ($column['show_label'] ?? false),
            ], (array) $data['columns'])),
            dateStart: $dateStart,
            dateEnd: $dateEnd,
            projectGroupId: isset($filters['project_group_id']) && is_numeric($filters['project_group_id'])
                ? (int) $filters['project_group_id']
                : null,
            projectStateIds: $ids($filters['project_state_ids'] ?? []),
            categoryIds: $ids($filters['category_ids'] ?? []),
            genreIds: $ids($filters['genre_ids'] ?? []),
            sectorIds: $ids($filters['sector_ids'] ?? []),
            eventTypeIds: $ids($filters['event_type_ids'] ?? []),
            memberIds: $ids($filters['member_ids'] ?? []),
            eventColumnTypeIds: $ids($data['event_column']['event_type_ids'] ?? []),
            templateId: isset($data['template_id']) && is_numeric($data['template_id'])
                ? (int) $data['template_id']
                : null,
        );
    }

    public function hasDateRange(): bool
    {
        return $this->dateStart !== null && $this->dateEnd !== null;
    }

    /**
     * Ein Projekt wird ausgegeben, wenn mindestens eines der gewählten Merkmale zutrifft.
     */
    public function hasCharacteristics(): bool
    {
        return $this->projectStateIds !== [] ||
            $this->categoryIds !== [] ||
            $this->genreIds !== [] ||
            $this->sectorIds !== [] ||
            $this->eventTypeIds !== [];
    }
}
