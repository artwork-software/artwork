<?php

use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $projectService = app(ProjectService::class);

        SageNotAssignedData::query()
            ->where('is_collective_booking', true)
            ->whereNull('project_id')
            ->whereNotNull('kst_traeger')
            ->where('kst_traeger', '!=', '')
            ->each(function (SageNotAssignedData $parent) use ($projectService): void {
                $project = $projectService->getProjectByCostCenter($parent->kst_traeger);
                if ($project) {
                    $parent->update(['project_id' => $project->id]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally left empty — cannot reliably determine which records were previously null
    }
};
