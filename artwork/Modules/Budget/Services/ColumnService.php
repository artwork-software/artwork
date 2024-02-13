<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\ColumnRepository;

class ColumnService
{

    public function __construct(
        private readonly ColumnRepository $columnRepository,
    )
    {
    }

    public function createColumnInTable(Table $table, string $name, string $subName, string $type, int $linked_first_column = 0, int $linked_second_column = 0): Column|Model
    {
        $column = new Column();
        $column->table_id = $table->id;
        $column->name = $name;
        $column->linked_first_column = $linked_first_column;
        $column->linked_second_column = $linked_second_column;
        $column->subName = $subName;
        $column->type = $type;
        return $this->columnRepository->save($column);
    }


    public function setColumnSubName(int $table_id): void
    {
        $table = Table::find($table_id);
        $columns = $table->columns()->get();

        $count = 1;

        foreach ($columns as $column) {
            // Skip columns without subname
            if ($column->subName === null || empty($column->subName)) {
                continue;
            }
            $column->update([
                'subName' => $this->getNameFromNumber($count)
            ]);
            $count++;
        }
    }

    public function getNameFromNumber(int $num): string
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2) . $letter;
        } else {
            return $letter;
        }
    }
}
