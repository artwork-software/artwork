<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Artwork\Modules\Inventory\Repositories\InventoryUserFilterRepository;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Inertia\Inertia;

class InventoryUserFilterShareService
{
    protected InventoryUserFilterRepository $filterRepo;

    public function __construct(InventoryUserFilterRepository $filterRepo)
    {
        $this->filterRepo = $filterRepo;
    }

    /**
     * Gibt alle Filtermöglichkeiten und User-Filter für Inertia:share zurück
     */
    public function getFilterDataForUser(User $user): void
    {
        $categories = InventoryCategory::select('id', 'name')->with([
            'subCategories:id,name,inventory_category_id'
        ])->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'sub_categories' => $cat->subCategories->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'inventory_category_id' => $sub->inventory_category_id,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $filterableProperties = InventoryArticleProperties::filterable()
            ->select('id', 'name', 'type', 'select_values')
            ->get()
            ->map(fn ($prop) => [
                'id' => $prop->id,
                'name' => $prop->name,
                'type' => $prop->type,
                'select_values' => $prop->select_values ?? [],
            ])->toArray();

        $userFilter = $this->filterRepo->getByUser($user);
        $userFilterArr = [
            'category_ids' => $userFilter?->category_ids ?? [],
            'sub_category_ids' => $userFilter?->sub_category_ids ?? [],
            'property_filters' => $userFilter?->property_filters ?? [],
            'tag_ids' => $userFilter?->tag_ids ?? [],
        ];

        Inertia::share([
            'categories' => $categories,
            'filterable_properties' => $filterableProperties,
            'user_filter' => $userFilterArr,

            // rooms/users/manufacturers als schlanke Arrays (toBase: Global Scopes wie SoftDeletes
            // greifen weiter): das Frontend liest nur diese Felder. Als Modelle kamen Room::$with
            // (admins/creator) bzw. die User-/CrmContact-Appends (profile_photo_url, full_name,
            // formated_work_time_balance …) mit — bei den Nutzern ~45 KB je Seitenaufruf.
            'rooms' => Room::query()
                ->toBase()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn (object $room): array => ['id' => (int) $room->id, 'name' => $room->name])
                ->all(),

            'projects' => Project::select('id', 'name')->orderBy('name')->get(),
            'users' => User::query()
                ->toBase()
                ->select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (object $user): array => [
                    'id' => (int) $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ])
                ->all(),
            'manufacturers' => CrmContact::query()
                    ->whereHas(
                        'contactType',
                        fn ($q) => $q->where('slug', CrmSystemContactTypeEnum::MANUFACTURER->value)
                    )
                    ->toBase()
                    ->select('id', 'display_name as name')
                    ->orderBy('display_name')
                    ->get()
                    ->map(fn (object $manufacturer): array => [
                        'id' => (int) $manufacturer->id,
                        'name' => $manufacturer->name,
                    ])
                    ->all(),

            // Tags für Filter
            'tags' => InventoryTag::select('id', 'name', 'color', 'inventory_tag_group_id')->orderBy('position')->get(),
            'tagGroups' => InventoryTagGroup::select('id', 'name')->orderBy('position')->get(),
        ]);
    }
}
