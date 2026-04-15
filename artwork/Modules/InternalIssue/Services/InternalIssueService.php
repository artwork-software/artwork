<?php

namespace Artwork\Modules\InternalIssue\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class InternalIssueService
{
    public function __construct(
        protected readonly InventoryArticleService $articleService,
        protected readonly AuthManager $auth,
    ){
    }

    public function store(array $data, array $files = []): InternalIssue
    {
        $issue = InternalIssue::create($data);

        if (!empty($files)) {
            $this->handleFiles($issue, $files);
        }

        if (isset($data['special_items'])) {
            $issue->specialItems()->delete();
            foreach ($data['special_items'] as $item) {
                $issue->specialItems()->create($item);
            }
        }

        // Artikel zuordnen über morph
        if (!empty($data['articles'])) {
            $this->syncArticles($issue, $data['articles']);
        }

        $issue->responsibleUsers()->sync($data['responsible_user_ids'] ?? []);

        $issue->load(['articles', 'project']);

        $this->logActivity($issue, 'created', 'Internal issue created', [
            'translation_key' => 'Internal issue created',
            'issue_type' => 'internal',
            'project_id' => $issue->project_id,
            'project_name' => $issue->project?->name,
            'issue_name' => $issue->name,
            'articles' => $issue->articles->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'quantity' => $a->pivot->quantity,
            ])->toArray(),
        ]);

        return $issue->fresh(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers']);
    }

    public function update(InternalIssue $issue, array $data, array $files = []): InternalIssue
    {
        // Capture old state (cast to strings for reliable comparison)
        $trackedFields = ['name', 'project_id', 'start_date', 'start_time', 'end_date', 'end_time', 'room_id', 'notes'];
        $oldAttributes = $this->normalizeAttributes($issue->only($trackedFields));
        $oldArticles = $issue->articles->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'quantity' => $a->pivot->quantity,
        ])->toArray();

        $issue->update($data);

        if (!empty($files)) {
            $this->handleFiles($issue, $files);
        }

        if (isset($data['special_items'])) {
            $issue->specialItems()->delete();
            $issue->update([
                'special_items_done' => $data['special_items_done'] ?? false,
            ]);
            foreach ($data['special_items'] as $item) {
                $issue->specialItems()->create($item);
            }
        }

        // Clear cached articles relation before sync to avoid stale data
        $issue->unsetRelation('articles');

        // Artikel zuordnen über morph
        if (!empty($data['articles'])) {
            $this->syncArticles($issue, $data['articles']);
        }

        $issue->responsibleUsers()->sync($data['responsible_user_ids'] ?? []);

        $issue->load(['articles', 'project']);
        $newAttributes = $this->normalizeAttributes($issue->only($trackedFields));
        $newArticles = $issue->articles->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'quantity' => $a->pivot->quantity,
        ])->toArray();

        // Only include actually changed fields
        [$changedOld, $changedNew] = $this->diffAttributes($oldAttributes, $newAttributes);

        $this->logActivity($issue, 'updated', 'Internal issue updated', [
            'translation_key' => 'Internal issue updated',
            'issue_type' => 'internal',
            'issue_name' => $issue->name,
            'project_id' => $issue->project_id,
            'project_name' => $issue->project?->name,
            'old' => $changedOld,
            'attributes' => $changedNew,
            'old_articles' => $oldArticles,
            'new_articles' => $newArticles,
        ]);

        return $issue->fresh(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers']);
    }

    public function delete(InternalIssue $issue): void
    {
        $issue->load(['project']);
        $issueName = $issue->name;
        $projectName = $issue->project?->name;
        $projectId = $issue->project_id;

        $this->logActivity($issue, 'deleted', 'Internal issue deleted', [
            'translation_key' => 'Internal issue deleted',
            'issue_type' => 'internal',
            'issue_name' => $issueName,
            'project_id' => $projectId,
            'project_name' => $projectName,
        ]);

        foreach ($issue->files as $file) {
            Storage::delete($file->file_path);
            $file->delete();
        }

        foreach ($issue->articles as $article) {
            $issue->articles()->detach($article);
        }

        foreach ($issue->specialItems as $item) {
            $item->delete();
        }

        $issue->delete();
    }

    protected function handleFiles(InternalIssue $issue, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('material-issue', 'public');
            InternalIssueFile::create([
                'internal_issue_id' => $issue->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    protected function syncArticles(InternalIssue $issue, array $articles): void
    {
        // Format: [['id' => 1, 'quantity' => 2], ...]
        $syncData = collect($articles)->mapWithKeys(fn($a) => [
            $a['id'] => ['quantity' => $a['quantity']],
        ])->toArray();

        $issue->articles()->sync($syncData);

        foreach ($articles as $article) {
            $articleFounded = $issue->articles->firstWhere('id', $article['id']);
            $this->articleService->checkAndNotifyOverbooking($articleFounded);
        }
    }

    public function deleteFile(InternalIssueFile $file): void
    {
        Storage::delete($file->file_path);
        $file->delete();
    }

    protected function logActivity(InternalIssue $issue, string $event, string $description, array $properties = []): void
    {
        activity('material_issue')
            ->performedOn($issue)
            ->causedBy($this->auth->user())
            ->event($event)
            ->tap(function (Activity $activity) use ($properties) {
                $activity->properties = $activity->properties->merge($properties);
            })
            ->log($description);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function diffAttributes(array $old, array $new): array
    {
        $changedOld = [];
        $changedNew = [];

        foreach ($new as $key => $newVal) {
            $oldVal = $old[$key] ?? null;
            if ((string) ($oldVal ?? '') !== (string) ($newVal ?? '')) {
                $changedOld[$key] = $oldVal;
                $changedNew[$key] = $newVal;
            }
        }

        return [$changedOld, $changedNew];
    }

    protected function normalizeAttributes(array $attributes): array
    {
        return array_map(function ($value) {
            if ($value instanceof \Illuminate\Support\Carbon || $value instanceof \DateTimeInterface) {
                return $value->format('d.m.Y');
            }
            // Normalize time strings HH:mm:ss → HH:mm
            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                return substr($value, 0, 5);
            }
            // Normalize date strings YYYY-MM-DD → DD.MM.YYYY
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return date('d.m.Y', strtotime($value));
            }
            return $value;
        }, $attributes);
    }
}
