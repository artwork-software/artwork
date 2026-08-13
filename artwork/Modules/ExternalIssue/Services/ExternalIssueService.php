<?php

namespace Artwork\Modules\ExternalIssue\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\DB;
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
        $issue = DB::transaction(function () use ($data, $files): ExternalIssue {
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

            return $issue;
        });

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
            'issue_date', 'return_date', 'material_value', 'return_remarks', 'project_id',
        ];
        $oldAttributes = $this->normalizeAttributes($issue->only($trackedFields));
        $oldArticles = $issue->articles->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'quantity' => $a->pivot->quantity,
        ])->toArray();

        DB::transaction(function () use ($issue, $data, $files): void {
            $issue->update($data);

            if (!empty($files)) {
                $this->handleFiles($issue, $files);
            }

            // Clear cached articles relation before sync to avoid stale data
            $issue->unsetRelation('articles');

            // An explicitly sent empty array must clear the assignment,
            // otherwise the last article can never be removed.
            if (array_key_exists('articles', $data)) {
                $this->syncArticles($issue, $data['articles'] ?? []);
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
        });

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
            Storage::disk('public')->delete($file->file_path);
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
            $path = $file->storeAs(
                'external_material_issues',
                StoredFileName::forUpload($file),
                'public'
            );
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
            // Soft-deleted articles pass the exists rule but are not in the
            // loaded relation — skip them instead of crashing.
            if ($articleFounded !== null) {
                $this->articleService->checkAndNotifyOverbooking($articleFounded);
            }
        }
    }

    public function deleteFile(ExternalIssueFile $file): void
    {
        Storage::disk('public')->delete($file->file_path);
        $file->delete();
    }

    /**
     * Rückgabe bestätigen (aus Benachrichtigung oder Übersicht) — setzt Status,
     * Empfänger und protokolliert die Bestätigung inkl. Freitext-Beschreibung.
     */
    public function confirmReturn(ExternalIssue $issue, ?string $remarks): ExternalIssue
    {
        $oldStatus = $issue->return_status;
        $oldRemarks = $issue->return_remarks;

        $updateData = [
            'return_status' => ExternalIssue::RETURN_STATUS_RETURNED,
        ];

        if ($remarks !== null) {
            $updateData['return_remarks'] = $remarks;
        }

        // Keep the original receiver when the action is triggered again.
        if ($issue->received_by_id === null) {
            $updateData['received_by_id'] = $this->auth->user()?->id;
        }

        // Early return frees the reserved quantity: cap the planned return
        // date to today so availability calculations stop counting it.
        $today = now()->startOfDay();
        if ($issue->return_date !== null && $today->lt($issue->return_date)) {
            $updateData['return_date'] = $today->toDateString();
        }

        $issue->update($updateData);

        $this->logActivity($issue, 'return_confirmed', 'External issue return confirmed', [
            'translation_key' => 'External issue return confirmed',
            'issue_type' => 'external',
            'issue_name' => $issue->name,
            'external_name' => $issue->external_name,
            'return_remarks' => $remarks,
            'old' => [
                'return_status' => $oldStatus,
                'return_remarks' => $oldRemarks,
            ],
            'attributes' => [
                'return_status' => ExternalIssue::RETURN_STATUS_RETURNED,
                'return_remarks' => $issue->return_remarks,
            ],
        ]);

        $this->resolveReturnDueNotifications($issue, ExternalIssue::RETURN_STATUS_RETURNED);

        return $issue;
    }

    /**
     * Rückmeldung "noch nicht zurück" — Material bleibt offen, die Bestätigung
     * kann später über Benachrichtigung oder Übersicht nachgeholt werden.
     */
    public function declineReturn(ExternalIssue $issue): ExternalIssue
    {
        $oldStatus = $issue->return_status;

        $issue->update(['return_status' => ExternalIssue::RETURN_STATUS_NOT_RETURNED]);

        $this->logActivity($issue, 'return_declined', 'External issue reported as not returned', [
            'translation_key' => 'External issue reported as not returned',
            'issue_type' => 'external',
            'issue_name' => $issue->name,
            'external_name' => $issue->external_name,
            'old' => ['return_status' => $oldStatus],
            'attributes' => ['return_status' => ExternalIssue::RETURN_STATUS_NOT_RETURNED],
        ]);

        $this->resolveReturnDueNotifications($issue, ExternalIssue::RETURN_STATUS_NOT_RETURNED);

        return $issue;
    }

    /**
     * Setzt offene Rückgabe-Erinnerungen aller Empfänger auf den Rückmeldestatus.
     * Nach "noch nicht zurück" bleibt der Bestätigen-Button erhalten, damit die
     * Rückgabe später direkt aus der Benachrichtigung gemeldet werden kann.
     */
    protected function resolveReturnDueNotifications(ExternalIssue $issue, string $status): void
    {
        $handledBy = $this->auth->user();

        $notifications = DB::table('notifications')
            ->where('data->type', NotificationEnum::NOTIFICATION_EXTERNAL_ISSUE_RETURN_DUE->value)
            ->where('data->modelId', $issue->id)
            ->get();

        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);
            $data['handledStatus'] = $status === ExternalIssue::RETURN_STATUS_RETURNED
                ? 'material_returned'
                : 'material_not_returned';
            $data['handledBy'] = $handledBy
                ? ['id' => $handledBy->id, 'name' => $handledBy->full_name]
                : null;
            $data['handledAt'] = now()->translatedFormat('d.m.Y H:i');
            $data['buttons'] = $status === ExternalIssue::RETURN_STATUS_RETURNED
                ? []
                : ['material_issue_return_confirm'];

            DB::table('notifications')
                ->where('id', $notification->id)
                ->update(['data' => json_encode($data)]);
        }
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
