<?php

namespace App\Http\Controllers;

use FiveamCode\LaravelNotionApi\Exceptions\HandlingException;
use FiveamCode\LaravelNotionApi\Exceptions\LaravelNotionAPIException;
use FiveamCode\LaravelNotionApi\Exceptions\NotionException;
use FiveamCode\LaravelNotionApi\Notion;
use FiveamCode\LaravelNotionApi\Query\Sorting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Symfony\Component\Routing\Annotation\Route;

class NotionController extends Controller
{
    protected $notion;

    public function __construct()
    {
        $this->notion = new Notion(env('NOTION_API_TOKEN'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sorting = new Collection();
        $sorting->add(Sorting::timestampSort("created_time", "descending"));
        $database = $this->notion
            ->database('193c7c5d737d80cfb0e4e2758c29f4ca')
            ->sortBy($sorting)
            ->query();
        $collectionItems = $database->asCollection();

        $items = $collectionItems->map(fn($item) => [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'properties' => $item->getProperties()
        ]);

        $content = [];
        $contentAsCollection = new Collection();

        if($request->get('updateId')) {
            try {
                $blocks = $this->notion->block($request->get('updateId'))->children();
                $content = $blocks->asTextCollection();
                $contentAsCollection = $blocks->asCollection();
            } catch (HandlingException | LaravelNotionAPIException | NotionException $e) {
                $blocks = null;
                return Redirect::route('notion.index');
            }

        }

        return Inertia::render('Updates/Index', [
            'items' => $items,
            'content' => $content,
            'contentAsCollection' => $contentAsCollection
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
