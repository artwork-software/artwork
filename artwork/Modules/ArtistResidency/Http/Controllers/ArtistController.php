<?php

namespace Artwork\Modules\ArtistResidency\Http\Controllers;

use App\Exports\ArtistExport;
use App\Http\Controllers\Controller;
use Artwork\Modules\ArtistResidency\Http\Requests\StoreArtistRequest;
use Artwork\Modules\ArtistResidency\Http\Requests\UpdateArtistRequest;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Project\Exports\BudgetsByBudgetDeadlineExport;
use Carbon\Carbon;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArtistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Artist/Index', [
            'artists' => Artist::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArtistRequest $request): void
    {
        Artist::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Artist $artist): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Artist $artist): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArtistRequest $request, Artist $artist): void
    {
        $artist->update($request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Artist $artist): void
    {
        // drop all residencies associated with this artist
        foreach ($artist->residencies as $residency) {
            $residency->update([
                'artist_id' => null
            ]);
        }

        $artist->delete();
    }

    // export via excel
    public function export(): BinaryFileResponse|null
    {
        $artists = Artist::all();

        return (new ArtistExport($artists))
            ->download(
                'artists_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx'
            )
            ->deleteFileAfterSend();
    }
}
