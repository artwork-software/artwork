<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CollectingSociety;
use App\Models\CompanyType;
use App\Models\ContractType;
use App\Models\Currency;
use App\Models\Genre;
use App\Models\Sector;
use Artwork\Modules\Project\Models\ProjectStates;
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
                'projects' => $category->projects
            ]),
            'genres' => Genre::all()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),
            'sectors' => Sector::all()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
            'contractTypes' => ContractType::all()->map(fn($contractType) => [
                'id' => $contractType->id,
                'name' => $contractType->name,
            ]),
            'companyTypes' => CompanyType::all()->map(fn($companyType) => [
                'id' => $companyType->id,
                'name' => $companyType->name,
            ]),
            'collectingSocieties' => CollectingSociety::all()->map(fn($collectingSociety) => [
                'id' => $collectingSociety->id,
                'name' => $collectingSociety->name,
            ]),
            'currencies' => Currency::all()->map(fn($currency) => [
                'id' => $currency->id,
                'name' => $currency->name,
            ]),
            'states' => ProjectStates::all()->map(fn($state) => [
                'id' => $state->id,
                'name' => $state->name,
                'color' => $state->color
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create([
            'name' => $request->name,
        ]);
        return Redirect::back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($request->only('name'));

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
