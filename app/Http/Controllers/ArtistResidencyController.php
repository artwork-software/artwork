<?php

namespace App\Http\Controllers;

use Artwork\Core\Enums\ExportType;
use Artwork\Modules\ArtistResidency\Http\Requests\ArtistResidencyCreateRequest;
use Artwork\Modules\ArtistResidency\Http\Requests\ArtistResidencyUpdateRequest;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\ArtistResidency\Services\ArtistResidencyService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function Spatie\LaravelPdf\Support\pdf;

class ArtistResidencyController extends Controller
{

    public function __construct(
        private readonly ArtistResidencyService $artistResidencyService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
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
    public function store(ArtistResidencyCreateRequest $request): void
    {
        $this->artistResidencyService->create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(ArtistResidency $artistResidency): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ArtistResidency $artistResidency): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArtistResidencyUpdateRequest $request, ArtistResidency $artistResidency): void
    {
        $this->artistResidencyService->update($artistResidency, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArtistResidency $artistResidency): void
    {
        $artistResidency->delete();
    }

    public function duplicate(ArtistResidency $artistResidency): void
    {
        $artistResidency->replicate()->save();
    }

    public function exportPdf(Project $project, string $language = 'en'): \Symfony\Component\HttpFoundation\Response
    {
        return $this->artistResidencyService->exportService($project, ExportType::PDF->value, $language);
    }

    public function exportExcel(Project $project, string $language = 'en'): \Symfony\Component\HttpFoundation\Response
    {
        return $this->artistResidencyService->exportService($project, ExportType::EXCEL->value, $language);
    }

    public function exportPerDiemPdf(Project $project, string $language = 'en'): \Symfony\Component\HttpFoundation\Response
    {
        return $this->artistResidencyService->exportPerDiemPdf($project, $language);
    }

    public function exportPdfDownload(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->artistResidencyService->downloadPdf($filename);
    }
}
