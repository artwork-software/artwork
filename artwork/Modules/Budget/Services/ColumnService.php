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
    ){}


    public function createColumnInTable(Table $table, string $name, string $subName, string $type, int $linked_first_column = 0, int $linked_second_column = 0): Column|Model
    {
        $column = new Column();
        $column->table_id = $table->id;
        $column->name = $name;
        $column->linked_first_column = $linked_first_column;
        $column->linked_second_column = $linked_second_column;
        return $this->columnRepository->save($column);
    }
}
