<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Seeder;

class UpdateOrCreateProjectRelevantColumn extends Seeder
{
    public function __construct(
        private readonly ColumnService $columnService,
        private readonly ColumnRelevanceService $columnRelevanceService,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();
        foreach ($projects as $project) {
            $table = $project?->table;
            if (!$table) {
                continue;
            }

            if ($project->is_group) {
                $column = $table->columns()->where('type', 'subprojects_column_for_group')->first();

                if (!$column) {
                    $newColumn = $this->columnService->createColumnInTable(
                        $table,
                        'Unterprojekte',
                        '-',
                        'subprojects_column_for_group',
                        $table->columns()->count() + 1
                    );

                    $createRowCell = static function (SubPositionRow $subPositionRow) use ($newColumn): void {
                        $subPositionRow->cells()->create([
                            'column_id' => $newColumn->id,
                            'value' => '0,00',
                            'verified_value' => null,
                            'linked_money_source_id' => null,
                            'commented' => $subPositionRow->commented,
                        ]);
                    };

                    $handleSubPosition = static function (SubPosition $subPosition) use (
                        $newColumn,
                        $createRowCell
                    ): void {
                        $subPosition->subPositionRows->each($createRowCell);

                        $newColumn->subPositionSumDetails()->create(['sub_position_id' => $subPosition->id]);
                    };

                    $table->mainPositions->each(
                        static function (MainPosition $mainPosition) use ($newColumn, $handleSubPosition): void {
                            $mainPosition->subPositions->each($handleSubPosition);

                            $newColumn->mainPositionSumDetails()->create(['main_position_id' => $mainPosition->id]);
                        }
                    );

                    $newColumn->budgetSumDetails()->create([
                        'type' => 'COST',
                    ]);

                    $newColumn->budgetSumDetails()->create([
                        'type' => 'EARNING',
                    ]);

                    $this->command->info('Project Group ' . $project->name . ' has been updated');
                }
            }

            // Invariante reparieren: genau eine budgetrelevante Wertspalte pro Tabelle
            // (behebt Mehrfach-Flags aus Spalten-Kopien sowie Gruppen/Projekte ohne Flag).
            $hadRelevantColumn = $table->columns()
                ->where('relevant_for_project_groups', true)
                ->count() === 1;

            $this->columnRelevanceService->ensureSingleRelevantColumn($table);

            if (!$hadRelevantColumn) {
                $this->command->info('Project ' . $project->name . ' has been updated');
            }
        }
    }
}
