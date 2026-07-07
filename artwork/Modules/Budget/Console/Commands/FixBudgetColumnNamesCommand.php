<?php

namespace Artwork\Modules\Budget\Console\Commands;

use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Budget\Models\Column;
use Illuminate\Console\Command;
use Throwable;

class FixBudgetColumnNamesCommand extends Command
{
    protected $signature = 'artwork:fix-budget-column-names';

    protected $description = '';

    private const array DEFAULT_NAMES = [
        0 => 'KTO',
        1 => 'KST',
        2 => 'Position',
    ];

    public function handle(): int
    {
        try {
            $this->ensureSettingsExist();
            $this->backfillColumnNames();
        } catch (Throwable $e) {
            $this->error('Fehler: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }

        $this->info('Fertig.');
        return self::SUCCESS;
    }

    private function ensureSettingsExist(): void
    {
        foreach (self::DEFAULT_NAMES as $position => $name) {
            $existing = BudgetColumnSetting::byColumnPosition($position)->first();

            if ($existing !== null) {
                continue;
            }

            $setting = new BudgetColumnSetting();
            $setting->forceFill([
                'column_position' => $position,
                'column_name' => $name,
            ])->save();
        }
    }

    private function backfillColumnNames(): void
    {
        $columns = Column::whereIn('position', array_keys(self::DEFAULT_NAMES))
            ->where(function ($query): void {
                $query->whereNull('name')->orWhere('name', '');
            })
            ->get();

        if ($columns->isEmpty()) {
            $this->info('Keine Columns mit leerem Namen an Position 0/1/2 gefunden.');
            return;
        }

        $perPosition = [0 => 0, 1 => 0, 2 => 0];
        $rows = [];

        foreach ($columns as $column) {
            $newName = self::DEFAULT_NAMES[$column->position] ?? null;

            if ($newName === null) {
                continue;
            }

            $rows[] = [
                $column->id,
                $column->table_id,
                $column->position,
                (string) $column->name,
                $newName,
            ];

            $column->name = $newName;
            $column->save();

            $perPosition[$column->position]++;
        }

        $this->table(['id', 'table_id', 'position', 'alter Name', 'neuer Name'], $rows);

        $label = 'aktualisiert';
        $this->info(
            sprintf(
                'Columns %s: pos0=%d, pos1=%d, pos2=%d, gesamt=%d',
                $label,
                $perPosition[0],
                $perPosition[1],
                $perPosition[2],
                array_sum($perPosition)
            )
        );
    }
}
