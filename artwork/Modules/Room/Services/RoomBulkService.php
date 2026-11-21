<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Change\Changes\Room\AttributeChange;
use Artwork\Modules\Change\Changes\Room\CategoryChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sammeländerungen für mehrere Räume: Kategorien und Eigenschaften zuweisen oder entfernen,
 * in ein anderes Areal verschieben, in den Papierkorb legen. Jede Änderung landet im Verlauf des Raums.
 */
readonly class RoomBulkService
{
    public function __construct(
        private CategoryChange $categoryChange,
        private AttributeChange $attributeChange,
        private ChangeService $changeService,
    ) {
    }

    /**
     * @param Collection<int, Room> $rooms
     * @param array<int, int> $categoryIds
     */
    public function changeCategories(Collection $rooms, array $categoryIds, bool $add): void
    {
        DB::transaction(function () use ($rooms, $categoryIds, $add): void {
            foreach ($rooms as $room) {
                $before = $room->replicate();
                $before->setRelation('categories', $room->categories()->get());

                $add
                    ? $room->categories()->syncWithoutDetaching($categoryIds)
                    : $room->categories()->detach($categoryIds);

                $room->unsetRelation('categories');
                $this->categoryChange->change($room, $before);
            }
        });
    }

    /**
     * @param Collection<int, Room> $rooms
     * @param array<int, int> $attributeIds
     */
    public function changeAttributes(Collection $rooms, array $attributeIds, bool $add): void
    {
        DB::transaction(function () use ($rooms, $attributeIds, $add): void {
            foreach ($rooms as $room) {
                $before = $room->replicate();
                $before->setRelation('attributes', $room->attributes()->get());

                $add
                    ? $room->attributes()->syncWithoutDetaching($attributeIds)
                    : $room->attributes()->detach($attributeIds);

                $room->unsetRelation('attributes');
                $this->attributeChange->change($room, $before);
            }
        });
    }

    /**
     * @param Collection<int, Room> $rooms
     * @return int Anzahl der tatsächlich verschobenen Räume
     */
    public function moveToArea(Collection $rooms, Area $area): int
    {
        return DB::transaction(function () use ($rooms, $area): int {
            $moved = 0;
            foreach ($rooms as $room) {
                if ($this->moveRoom($room, $area)) {
                    $moved++;
                }
            }

            return $moved;
        });
    }

    /**
     * Verschiebt einen Raum in ein anderes Areal und hält das im Verlauf fest.
     */
    public function moveRoom(Room $room, Area $area): bool
    {
        if ((int) $room->area_id === (int) $area->id) {
            return false;
        }

        $previousAreaName = $room->area()->withTrashed()->value('name') ?? '';
        $room->update(['area_id' => $area->id]);
        $room->unsetRelation('area');

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($room->id)
                ->setTranslationKey('Moved from area {0} to area {1}')
                ->setTranslationKeyPlaceholderValues([$previousAreaName, $area->name])
        );

        return true;
    }

    /**
     * @param Collection<int, Room> $rooms
     */
    public function moveToTrash(Collection $rooms): int
    {
        return DB::transaction(static function () use ($rooms): int {
            $rooms->each(static fn (Room $room) => $room->delete());

            return $rooms->count();
        });
    }
}
