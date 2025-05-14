<?php

namespace Artwork\Modules\InternalIssue\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Illuminate\Support\Facades\Storage;

class InternalIssueService
{
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

        return $issue->fresh(['files', 'articles', 'specialItems']);
    }

    public function update(InternalIssue $issue, array $data, array $files = []): InternalIssue
    {
        $issue->update($data);

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

        return $issue->fresh(['files', 'articles', 'specialItems']);
    }

    public function delete(InternalIssue $issue): void
    {
        foreach ($issue->files as $file) {
            Storage::delete($file->file_path);
            $file->delete();
        }
        $issue->delete();
    }

    protected function handleFiles(InternalIssue $issue, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('materialausgabe');
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
    }
}