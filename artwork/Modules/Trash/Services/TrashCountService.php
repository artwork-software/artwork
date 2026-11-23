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
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Shift\Services\ShiftTrashService;
use Artwork\Modules\User\Models\User;

/**
 * Zahl der Einträge je Papierkorb-Tab. Geliefert werden nur Tabs, die die Person öffnen darf;
 * die Regeln entsprechen den Routen der Tabs. Der Budget-Tab hat keine Zahl (Baumansicht).
 */
class TrashCountService
{
    public function __construct(private readonly ShiftTrashService $shiftTrashService)
    {
    }

    /**
     * @return array<string, int> Schlüssel = Inertia-Seite des Tabs
     */
    public function countsFor(User $user): array
    {
        $counts = [];

        if ($user->can(PermissionEnum::TRASH_ACCESS->value)) {
            $counts['Trash/Projects'] = Project::onlyTrashed()->count();
            $counts['Trash/Areas'] = Area::onlyTrashed()->count();
            $counts['Trash/Rooms'] = Room::onlyTrashed()->count();
            $counts['Trash/Events'] = Event::onlyTrashed()->count();
            $counts['Trash/ProjectSettings'] = Genre::onlyTrashed()->count()
                + Category::onlyTrashed()->count()
                + Sector::onlyTrashed()->count()
                + ProjectState::onlyTrashed()->count()
                + ContractType::onlyTrashed()->count()
                + CompanyType::onlyTrashed()->count()
                + Currency::onlyTrashed()->count();

            if ($user->can(PermissionEnum::SHIFT_PLANNER->value)) {
                $counts['Trash/Shifts'] = $this->shiftTrashService->trashedQuery($user)->count();
            }
        }

        if (
            $user->canAny([
                PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
                PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
            ])
        ) {
            $counts['Trash/SageNotAssignedData'] = SageNotAssignedData::onlyTrashed()->count();
        }

        if ($user->can(PermissionEnum::BUDGET_SETTINGS_UPDATE->value)) {
            $counts['Trash/BudgetManagementAccount'] = BudgetManagementAccount::onlyTrashed()->count();
            $counts['Trash/BudgetManagementCostUnit'] = BudgetManagementCostUnit::onlyTrashed()->count();
        }

        if ($user->can(PermissionEnum::INVENTORY_DELETE->value)) {
            $counts['Trash/InventoryArticles'] = InventoryArticle::onlyTrashed()->count();
        }

        if ($user->can(PermissionEnum::CRM_MANAGER->value)) {
            $counts['Trash/CrmContacts'] = CrmContact::onlyTrashed()->count();
        }

        return $counts;
    }
}
