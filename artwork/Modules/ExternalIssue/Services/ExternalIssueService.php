<?php

namespace Artwork\Modules\ExternalIssue\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;

class ExternalIssueService
{
    public function store(array $data, User $user, array $files = []): ExternalIssue
    {
        $data['issued_by_id'] = $user->id;
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

        return $issue->fresh(['files', 'articles']);
    }

    public function update(ExternalIssue $issue, array $data, array $files = []): ExternalIssue
    {
        $issue->update($data);

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

        return $issue->fresh(['files', 'articles']);
    }

    public function delete(ExternalIssue $issue): void
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
    }
}