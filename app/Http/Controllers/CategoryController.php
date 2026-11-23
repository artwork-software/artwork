<?php

namespace App\Http\Controllers;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Sector\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Artwork\Core\Validation\MasterDataName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class);
    }

    public function index(): Response|ResponseFactory
    {
        return inertia('Settings/ProjectSettings', [
            'categories' => Category::with('projects:id,name')->select([
                'id',
                'name',
                'color',
            ])->get()->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'projects' => $category->projects
            ]),
            'genres' => Genre::with('projects:id,name')->select(['id', 'name', 'color'])->get()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'color' => $genre->color,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::with('projects:id,name')->select(['id', 'name', 'color'])->get()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'color' => $sector->color,
                'projects' => $sector->projects
            ]),
            // usage_count: wie viele Verträge bzw. Projekte den Eintrag verwenden (Hinweis vor dem Löschen)
            'contractTypes' => $this->withUsageCount(
                ContractType::select(['id', 'name', 'color'])->get(),
                'contracts',
                'contract_type_id'
            ),
            'companyTypes' => $this->withUsageCount(
                CompanyType::select(['id', 'name', 'color'])->get(),
                'contracts',
                'company_type_id'
            ),
            'currencies' => $this->withUsageCount(
                Currency::select(['id', 'name', 'color'])->get(),
                'contracts',
                'currency_id'
            ),
            'states' => $this->withUsageCount(
                ProjectState::select(['id', 'name', 'color', 'is_planning'])->get(),
                'projects',
                'state'
            ),
            'createSettings' => app(ProjectCreateSettings::class),
        ]);
    }

    /**
     * Hängt jedem Eintrag die Zahl der nicht gelöschten Zeilen an, die auf ihn zeigen. Eine Abfrage je Liste.
     *
     * @param \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> $items
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function withUsageCount(
        \Illuminate\Support\Collection $items,
        string $table,
        string $foreignKey
    ): \Illuminate\Support\Collection {
        $counts = \Illuminate\Support\Facades\DB::table($table)
            ->whereNull('deleted_at')
            ->whereIn($foreignKey, $items->pluck('id'))
            ->groupBy($foreignKey)
            ->selectRaw($foreignKey . ' as item_id, count(*) as usage_count')
            ->pluck('usage_count', 'item_id');

        return $items->each(
            static fn ($item) => $item->setAttribute('usage_count', (int) ($counts[$item->getKey()] ?? 0))
        );
    }

    public function artistResidencySettings(): Response|ResponseFactory
    {
        $this->authorize('viewAny', Category::class);

        $generalSettings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);

        return inertia('Settings/ArtistResidencySettings', [
            'breakfastDeductionPerDay' => $generalSettings->breakfast_deduction_per_day,
            'artistResidencyDoNotSaveDefault' => $generalSettings->artist_residency_do_not_save_default,
            'artistResidencyDailyAllowanceDefault' => $generalSettings->artist_residency_daily_allowance_default,
            'artistResidencyNameColumns' => $generalSettings->artist_residency_name_columns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MasterDataName::validate($request);

        Category::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        MasterDataName::validate($request, updating: true);

        $category->update($request->only(['name', 'color']));

        return Redirect::back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        return Redirect::back();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        $category->forceDelete();

        return Redirect::route('projects.settings.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        $category->restore();

        return Redirect::route('projects.settings.trashed');
    }
}
