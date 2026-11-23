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
            $this->recordRoomsOfArea($model, $userId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Ein Areal nimmt seine Räume ohne Modell-Events mit in den Papierkorb. Damit sie dort nicht
     * ohne "Gelöscht von" stehen, bekommen sie denselben Vermerk, sofern sie noch keinen haben.
     */
    private function recordRoomsOfArea(Model $model, int|string $userId): void
    {
        if (!$model instanceof Area) {
            return;
        }

        $roomType = (new Room())->getMorphClass();
        $now = now();
        $rows = Room::onlyTrashed()
            ->where('area_id', $model->getKey())
            // nur die Räume dieses Löschvorgangs: früher gelöschte Räume gehören nicht dieser Person
            ->where('deleted_at', '>=', $now->copy()->subMinute())
            ->pluck('id')
            ->map(static fn (int $roomId): array => [
                'subject_type' => $roomType,
                'subject_id' => $roomId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            TrashRecord::query()->insertOrIgnore($rows);
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
