<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
        return inertia('Settings/CategoryManagement', [
            'categories' => Category::paginate(10)->through(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Category::create([
            'name' => $request->name,
        ]);
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
}
