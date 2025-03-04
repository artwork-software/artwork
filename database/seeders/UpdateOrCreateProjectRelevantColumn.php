<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateOrCreateProjectRelevantColumn extends Seeder
{
    public function __construct(
        private readonly ColumnService $columnService,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();



        foreach ($projects as $project) {
            if ($project->is_group) {
                $table = $project->table;
                $column = $table->columns()->where('type', 'project_relevant_column')->first();

                if (!$column) {
                    $newColumn = $this->columnService->createColumnInTable(
                        $table,
                        'Unterprojekte',
                        '-',
                        'project_relevant_column',
                        $table->columns()->count() + 1
                    );

                    $table->mainPositions->each(function (MainPosition $mainPosition) use ($newColumn): void {
                        $mainPosition->subPositions->each(function (SubPosition $subPosition) use ($newColumn): void {
                            $subPosition->subPositionRows->each(function (SubPositionRow $subPositionRow) use ($newColumn): void {
                                $subPositionRow->cells()->create([
                                    'column_id' => $newColumn->id,
                                    'value' => '0,00',
                                    'verified_value' => null,
                                    'linked_money_source_id' => null,
                                    'commented' => $subPositionRow->commented,
                                ]);
                            });

                            $newColumn->subPositionSumDetails()->create(['sub_position_id' => $subPosition->id]);
                        });

                        $newColumn->mainPositionSumDetails()->create(['main_position_id' => $mainPosition->id]);
                    });

                    $newColumn->budgetSumDetails()->create([
                        'type' => 'COST',
                    ]);

                    $newColumn->budgetSumDetails()->create([
                        'type' => 'EARNING',
                    ]);

                    $this->command->info('Project Group ' . $project->name . ' has been updated');
                }



            } else {
                // set last column to relevant_for_project_groups if no column is relevant_for_project_groups
                $table = $project->table;
                $columns = $table->columns()->get();
                $lastColumn = $columns->last();

                if (!$lastColumn->relevant_for_project_groups) {
                    $lastColumn->update([
                        'relevant_for_project_groups' => true
                    ]);

                    // send console message
                    $this->command->info('Project ' . $project->name . ' has been updated');
                }
            }
        }

    }
}
