<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CollectingSociety;
use App\Models\CompanyType;
use App\Models\ContractType;
use App\Models\Currency;
use App\Models\Genre;
use App\Models\ProjectStates;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CategoryController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Category::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Category::create([
            'name' => $request->name,
        ]);
        return Redirect::back()->with('success', 'Category created');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     */
    public function update(Request $request, Category $category)
    {
        $category->update($request->only('name'));

        /*
        if (Auth::user()->can('update projects')) {
            $category->projects()->sync(
                collect($request->assigned_project_ids)
                    ->map(function ($project_id) {
                        return $project_id;
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign projects to a category.'], 403);
        }
        */
        return Redirect::back()->with('success', 'Category edited');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return Redirect::back()->with('success', 'Category deleted');
    }

    public function forceDelete(int $id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        $category->forceDelete();

        return Redirect::route('project.settings.trashed')->with('success', 'Category deleted');
    }

    public function restore(int $id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);

        $category->restore();

        return Redirect::route('projects.settings.trashed')->with('success', 'Category restored');
    }
}
