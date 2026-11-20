<?php

namespace Artwork\Modules\Trash\Services;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Trash\Models\TrashRecord;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Hält fest, wer einen Eintrag in den Papierkorb gelegt hat. Gilt nur für die Modelle, die der
 * Papierkorb anzeigt. Ohne angemeldete Person (Befehle, Jobs) wird nichts festgehalten.
 */
class TrashRecorder
{
    /** @var array<int, class-string<Model>> */
    public const TRACKED_MODELS = [
        Project::class,
        Area::class,
        Room::class,
        Event::class,
        Shift::class,
        Genre::class,
        Category::class,
        Sector::class,
        ProjectState::class,
        ContractType::class,
        CompanyType::class,
        Currency::class,
        SageNotAssignedData::class,
        BudgetManagementAccount::class,
        BudgetManagementCostUnit::class,
        InventoryArticle::class,
        CrmContact::class,
    ];

    public function register(Dispatcher $events): void
    {
        foreach (self::TRACKED_MODELS as $modelClass) {
            $events->listen('eloquent.deleted: ' . $modelClass, fn (Model $model) => $this->onDeleted($model));
            $events->listen('eloquent.restored: ' . $modelClass, fn (Model $model) => $this->forget($model));
            $events->listen('eloquent.forceDeleted: ' . $modelClass, fn (Model $model) => $this->forget($model));
        }
    }

    private function onDeleted(Model $model): void
    {
        $userId = Auth::id();
        if ($userId === null || !$model->exists || !method_exists($model, 'trashed') || !$model->trashed()) {
            return;
        }

        // Das Protokoll ist Beiwerk: ein Fehler hier darf das Löschen nicht verhindern
        try {
            TrashRecord::query()->updateOrCreate(
                ['subject_type' => $model->getMorphClass(), 'subject_id' => $model->getKey()],
                ['user_id' => $userId]
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function forget(Model $model): void
    {
        try {
            TrashRecord::query()
                ->where('subject_type', $model->getMorphClass())
                ->where('subject_id', $model->getKey())
                ->delete();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
