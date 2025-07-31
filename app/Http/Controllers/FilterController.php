<?php

namespace App\Http\Controllers;

use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;
use Illuminate\Support\Collection as Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @todo: Use FilterService and its FilterRepository
 */
class FilterController extends Controller
{
    public function __construct(private readonly FilterService $filterService)
    {
    }

    public function index(): Collection
    {
        return $this->filterService->getPersonalFilter(Auth::user());
    }


    public function activate(UserFilterTemplate $filter, User $user): void
    {
        $user->userFilters()->updateOrCreate([
            'filter_type' => $filter->filter_type
        ], [
            'room_ids' => $filter->room_ids,
            'area_ids' => $filter->area_ids,
            'room_category_ids' => $filter->room_category_ids,
            'room_attribute_ids' => $filter->room_attribute_ids,
            'event_type_ids' => $filter->event_type_ids,
            'event_property_ids' => $filter->event_property_ids,
            'craft_ids' => $filter->craft_ids,
        ]);
    }
}
