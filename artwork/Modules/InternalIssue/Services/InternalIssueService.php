<?php

namespace Artwork\Modules\InternalIssue\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Support\Facades\Storage;

class InternalIssueService
{
    public function __construct(
        protected readonly InventoryArticleService $articleService,
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

        return $issue->fresh(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers']);
    }

    public function update(InternalIssue $issue, array $data, array $files = []): InternalIssue
    {
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

        // Artikel zuordnen über morph
        if (!empty($data['articles'])) {
            $this->syncArticles($issue, $data['articles']);
        }

        $issue->responsibleUsers()->sync($data['responsible_user_ids'] ?? []);

        return $issue->fresh(['files', 'articles', 'specialItems', 'room', 'project', 'responsibleUsers']);
    }

    public function delete(InternalIssue $issue): void
    {
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

}