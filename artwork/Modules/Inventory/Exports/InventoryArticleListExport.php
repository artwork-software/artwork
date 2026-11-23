<?php

namespace Artwork\Modules\Inventory\Exports;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Artikelübersicht des Inventars als Excel-Tabelle: Stammdaten, Mengen je Status, Tags und
 * die Eigenschaften der enthaltenen Artikel als eigene Spalten.
 */
class InventoryArticleListExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    /** @var Collection<int, InventoryArticleProperties> */
    private Collection $properties;

    /**
     * @param Collection<int, InventoryArticle> $articles mit geladenen category, subCategory, properties,
     *                                                    statusValues, detailedArticleQuantities, tags
     * @param Collection<int, InventoryArticleStatus> $statuses in Anzeigereihenfolge
     */
    public function __construct(
        private readonly Collection $articles,
        private readonly Collection $statuses,
        private readonly string $inventoryNumberPrefix = ''
    ) {
        InventoryArticle::preloadPropertyLookups($articles);
        $this->properties = $articles
            ->flatMap(static fn (InventoryArticle $article) => $article->properties)
            ->unique('id')
            ->sortBy([['order', 'asc'], ['id', 'asc']])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Name'),
            __('Inventory number'),
            __('Description'),
            __('Category'),
            __('Subcategory'),
            __('Quantity'),
            ...$this->statuses->pluck('name')->all(),
            __('Tags'),
            ...$this->properties->pluck('name')->all(),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return $this->articles
            ->map(fn (InventoryArticle $article): array => [
                $article->name,
                $article->inventory_number !== null && $article->inventory_number !== ''
                    ? $this->inventoryNumberPrefix . $article->inventory_number
                    : null,
                $article->description,
                $article->category?->name,
                $article->subCategory?->name,
                $article->quantity,
                ...$this->statuses
                    ->map(fn (InventoryArticleStatus $status): int|float => $this->statusQuantity($article, $status))
                    ->all(),
                $article->tags->pluck('name')->implode(', '),
                ...$this->properties
                    ->map(fn (InventoryArticleProperties $property): ?string => $this->propertyValue(
                        $article,
                        $property
                    ))
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    /**
     * Menge im Status: bei Einzelinventar die Summe der Einzelartikel, sonst der gepflegte Statuswert.
     */
    private function statusQuantity(InventoryArticle $article, InventoryArticleStatus $status): int|float
    {
        if ($article->is_detailed_quantity) {
            return $article->detailedArticleQuantities
                ->where('inventory_article_status_id', $status->id)
                ->sum('quantity');
        }

        return (float) ($article->statusValues->firstWhere('id', $status->id)?->pivot?->value ?? 0);
    }

    private function propertyValue(InventoryArticle $article, InventoryArticleProperties $property): ?string
    {
        $value = $article->properties->firstWhere('id', $property->id)?->pivot?->value;

        if ($value === null || $value === '') {
            return null;
        }

        return match ($property->type) {
            'room' => InventoryArticle::resolveRoom($value)?->name,
            'manufacturer' => InventoryArticle::resolveManufacturer($value)?->display_name,
            'checkbox' => in_array(strtolower(trim((string) $value)), ['', '0', 'false', 'off'], true)
                ? __('No')
                : __('Yes'),
            'date' => $this->formatDate((string) $value),
            'file' => basename((string) $value),
            default => (string) $value,
        };
    }

    private function formatDate(string $value): string
    {
        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
