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
            'categories' => Category::with('projects:id,name')->select(['id', 'name', 'color'])->get()->map(fn($category) => [
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
            'contractTypes' => ContractType::select(['id', 'name', 'color'])->get(),
            'companyTypes' => CompanyType::select(['id', 'name', 'color'])->get(),
            'currencies' => Currency::select(['id', 'name', 'color'])->get(),
            'states' => ProjectState::select(['id', 'name', 'color', 'is_planning'])->get(),
            'createSettings' => app(ProjectCreateSettings::class),
            'breakfastDeductionPerDay' => app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class)->breakfast_deduction_per_day
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create([
            'name' => $request->name,
            'color' => $request->color
        ]);
        return Redirect::back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
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
