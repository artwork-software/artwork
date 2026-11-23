<?php

namespace Artwork\Modules\MoneySource\Exports;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Übersicht der Finanzierungsquellen als Excel-Tabelle: Volumen, Verbrauch, Zeiträume, Kategorien.
 */
class MoneySourceListExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * @param Collection<int, MoneySource> $moneySources bereits in der gewünschten Reihenfolge,
     *                                                   mit geladenen categories und moneySourceTasks
     */
    public function __construct(
        private readonly Collection $moneySources,
        private readonly MoneySourceCalculationService $moneySourceCalculationService
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Name'),
            __('Type'),
            __('Group'),
            __('Funder'),
            __('Volume'),
            __('Used'),
            __('Available'),
            __('Funding period from'),
            __('Funding period until'),
            __('Term from'),
            __('Term until'),
            __('Categories'),
            __('Open tasks'),
            __('Description'),
        ];
    }

    /**
     * @return array<int, array<int, string|float|int|null>>
     */
    public function array(): array
    {
        $groupNames = MoneySource::query()
            ->whereIn('id', $this->moneySources->pluck('group_id')->filter()->unique())
            ->pluck('name', 'id');

        return $this->moneySources
            ->map(function (MoneySource $moneySource) use ($groupNames): array {
                $amount = (float) $moneySource->amount;
                $positionSum = $this->moneySourceCalculationService->getPositionSumOfOneMoneySource($moneySource);

                return [
                    $moneySource->name,
                    $moneySource->is_group ? __('Funding group') : __('Source of funding'),
                    $moneySource->group_id ? ($groupNames[$moneySource->group_id] ?? null) : null,
                    $moneySource->source_name,
                    $amount,
                    // Ausgaben gehen negativ in die Positionssumme ein
                    round(-$positionSum, 2),
                    round($amount + $positionSum, 2),
                    $this->formatDate($moneySource->funding_start_date),
                    $this->formatDate($moneySource->funding_end_date),
                    $this->formatDate($moneySource->start_date),
                    $this->formatDate($moneySource->end_date),
                    $moneySource->categories->pluck('name')->implode(', '),
                    $moneySource->moneySourceTasks
                        ->filter(static fn (MoneySourceTask $task): bool => !$task->done)
                        ->count(),
                    $moneySource->description,
                ];
            })
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

    private function formatDate(mixed $date): ?string
    {
        return $date ? Carbon::parse($date)->format('d.m.Y') : null;
    }
}
