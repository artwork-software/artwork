<?php

namespace App\Support\Services;

use App\Models\Checklist;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HistoryService
{
    public function projectUpdated(Project $project): Collection
    {
        return $this->modelUpdated($project, $project);
    }

    public function updateHistory(Project $project, string $text): Collection
    {
        return collect($project->project_histories()->create([
            'user_id' => Auth::id(),
            'description' => $text
        ]));
    }

    public function taskUpdated(Task $task): ProjectHistory|Collection
    {
        $wildCards = ['{name}' => $task->name, '{checklistName}' => $task->checklist->name];

        return $this->modelUpdated($task, $task->checklist->project, $wildCards);
    }

    public function modelUpdated(Model $model, Project $project, array $swap = []): null|ProjectHistory|Collection
    {
        $config = config('history.' . Str::lower(class_basename($model)));

        // is nothing is configured, don't keep the history
        if (! $config) {
            return null;
        }

        // if the model was deleted and the config is provided, add History
        if (($config['deleted'] ?? false) && ! $model->exists) {
            $description = Str::of($config['deleted'])->swap($swap)->toString();

            return $this->updateHistory($project, $description);
        }

        // if the model was created and the config is provided, add History
        if (($config['created'] ?? false) && $model->wasRecentlyCreated) {
            $description = Str::of($config['created'])->swap($swap)->toString();

            return $this->updateHistory($project, $description);
        }

        // foreach property provided in the config
        return collect($config['properties'] ?? [])
            ->map(function (array $config, string $attribute) use ($project, $swap, $model) {
                // check if the property was changed
                if ($model->isClean($attribute)) {
                    return null;
                }

                // guess the action that was performed on the action
                $action = ($model->getOriginal($attribute) === null) ? 'added'
                    : ($model->getAttribute($attribute) === null ? 'deleted' : 'updated');

                // get the provided config text
                $textByConfig = $config[$action] ?? false;

                if (! $textByConfig) {
                    return null;
                }

                // swap all wild cards
                $description = Str::swap(array_merge([
                    '{new}' => $model->getAttribute($attribute),
                    '{old}' => $model->getRawOriginal($attribute),
                ], $swap), $textByConfig);

                return $this->updateHistory($project, $description);
            })->filter();
    }

    public function checklistUpdated(Checklist $checklist): Collection
    {
        return $this->modelUpdated($checklist, $checklist->project, ['name' => $checklist->name]);
    }

}
