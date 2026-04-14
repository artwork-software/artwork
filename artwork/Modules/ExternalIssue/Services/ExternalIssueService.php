<?php

namespace Artwork\Modules\ExternalIssue\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class ExternalIssueService
{

    public function __construct(
        protected readonly InventoryArticleService $articleService,
        protected readonly AuthManager $auth,
    ) {
    }

    public function store(array $data, array $files = []): ExternalIssue
    {
        $issue = ExternalIssue::create($data);

        if (!empty($files)) {
            $this->handleFiles($issue, $files);
        }

        if (!empty($data['articles'])) {
            $this->syncArticles($issue, $data['articles']);
        }

        if (isset($data['special_items'])) {
            $issue->specialItems()->delete();
            foreach ($data['special_items'] as $item) {
                $issue->specialItems()->create($item);
            }
        }

        $issue->load('articles');

        $this->logActivity($issue, 'created', 'External issue created', [
            'translation_key' => 'External issue created',
            'issue_type' => 'external',
            'issue_name' => $issue->name,
            'external_name' => $issue->external_name,
            'articles' => $issue->articles->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'quantity' => $a->pivot->quantity,
            ])->toArray(),
        ]);

        return $issue->fresh(['files', 'articles']);
    }

    public function update(ExternalIssue $issue, array $data, array $files = []): ExternalIssue
    {
        // Capture old state (cast to strings for reliable comparison)
        $trackedFields = [
            'name', 'external_name', 'external_address', 'external_email', 'external_phone',
            'issue_date', 'return_date', 'material_value', 'return_remarks',
        ];
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

        // Clear cached articles relation before sync to avoid stale data
        $issue->unsetRelation('articles');

        if (!empty($data['articles'])) {
            $this->syncArticles($issue, $data['articles']);
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

        $issue->load('articles');
        $newAttributes = $this->normalizeAttributes($issue->only($trackedFields));
        $newArticles = $issue->articles->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'quantity' => $a->pivot->quantity,
        ])->toArray();

        // Only include actually changed fields
        [$changedOld, $changedNew] = $this->diffAttributes($oldAttributes, $newAttributes);

        $this->logActivity($issue, 'updated', 'External issue updated', [
            'translation_key' => 'External issue updated',
            'issue_type' => 'external',
            'issue_name' => $issue->name,
            'external_name' => $issue->external_name,
            'old' => $changedOld,
            'attributes' => $changedNew,
            'old_articles' => $oldArticles,
            'new_articles' => $newArticles,
        ]);

        return $issue->fresh(['files', 'articles']);
    }

    public function delete(ExternalIssue $issue): void
    {
        $issueName = $issue->name;
        $externalName = $issue->external_name;

        $this->logActivity($issue, 'deleted', 'External issue deleted', [
            'translation_key' => 'External issue deleted',
            'issue_type' => 'external',
            'issue_name' => $issueName,
            'external_name' => $externalName,
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

    protected function handleFiles(ExternalIssue $issue, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('external_material_issues', 'public');
            ExternalIssueFile::create([
                'external_issue_id' => $issue->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    protected function syncArticles(ExternalIssue $issue, array $articles): void
    {
        $syncData = collect($articles)->mapWithKeys(fn($a) => [
            $a['id'] => ['quantity' => $a['quantity']],
        ])->toArray();

        $issue->articles()->sync($syncData);

        foreach ($articles as $article) {
            $articleFounded = $issue->articles->firstWhere('id', $article['id']);
            $this->articleService->checkAndNotifyOverbooking($articleFounded);
        }
    }

    public function deleteFile(ExternalIssueFile $file): void
    {
        Storage::delete($file->file_path);
        $file->delete();
    }

    public function logActivity(ExternalIssue $issue, string $event, string $description, array $properties = []): void
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
