<?php

namespace Artwork\Core\Traits;

use Antonrom\ModelChangesHistory\Facades\HistoryStorage;
use Antonrom\ModelChangesHistory\Models\Change;
use Antonrom\ModelChangesHistory\Observers\ModelChangesHistoryObserver;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

/**
 * Local replacement for Antonrom\ModelChangesHistory\Traits\HasChangesHistory.
 */
trait HasChangesHistory
{
    public static function bootHasChangesHistory(): void
    {
        $observer = ModelChangesHistoryObserver::class;

        $events = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

        foreach ($events as $event) {
            if (method_exists($observer, $event)) {
                static::registerModelEvent($event, $observer . '@' . $event);
            }
        }
    }

    public function latestChange(): ?Change
    {
        return HistoryStorage::getLatestChange($this);
    }

    public function latestChangeMorph(): MorphOne
    {
        return $this->morphOne(Change::class, 'model')->latest();
    }

    public function historyChanges(): Collection
    {
        return HistoryStorage::getHistoryChanges($this);
    }

    public function historyChangesMorph(): MorphMany
    {
        return $this->morphMany(Change::class, 'model')->latest();
    }

    public function clearHistoryChanges(): void
    {
        HistoryStorage::deleteHistoryChanges($this);
    }
}
