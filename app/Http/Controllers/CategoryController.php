<?php

namespace App\Http\Controllers;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\ContractType\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectStates;
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
            'categories' => Category::all()->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'projects' => $category->projects
            ]),
            'genres' => Genre::all()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'color' => $genre->color,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::all()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'color' => $sector->color,
                'projects' => $sector->projects
            ]),
            'contractTypes' => ContractType::all()->map(fn($contractType) => [
                'id' => $contractType->id,
                'name' => $contractType->name,
                'color' => $contractType->color,
            ]),
            'companyTypes' => CompanyType::all()->map(fn($companyType) => [
                'id' => $companyType->id,
                'name' => $companyType->name,
                'color' => $companyType->color,
            ]),
            'collectingSocieties' => CollectingSociety::all()->map(fn($collectingSociety) => [
                'id' => $collectingSociety->id,
                'name' => $collectingSociety->name,
                'color' => $collectingSociety->color,
            ]),
            'currencies' => Currency::all()->map(fn($currency) => [
                'id' => $currency->id,
                'name' => $currency->name,
                'color' => $currency->color,
            ]),
            'states' => ProjectStates::all()->map(fn($state) => [
                'id' => $state->id,
                'name' => $state->name,
                'color' => $state->color
            ]),
            'createSettings' => app(ProjectCreateSettings::class)
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
