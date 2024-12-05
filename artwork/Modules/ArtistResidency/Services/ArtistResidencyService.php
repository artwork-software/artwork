<?php

namespace Artwork\Modules\ArtistResidency\Services;

use Artwork\Core\Enums\ExportType;
use Artwork\Modules\ArtistResidency\Exports\ArtistResidencyExcelExport;
use Artwork\Modules\ArtistResidency\Repositories\ArtistResidencyRepository;
use Artwork\Modules\Project\Models\Project;
use Barryvdh\DomPDF\PDF;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class ArtistResidencyService
{
    public function __construct(
        private ArtistResidencyRepository $artistResidencyRepository,
        private PDF $pdf,
        private FilesystemManager $filesystemManager,
        private InertiaResponseFactory $inertiaResponseFactory,
        private ResponseFactory $responseFactory,
        private AuthManager $authManager
    ) {
    }

    /**
     * Exports artist residency data based on project and type.
     */
    public function exportService(Project $project, string $type, string $language): Response|BinaryFileResponse
    {
        $artistResidencies = $this->getArtistResidenciesByProjectId($project->id);

        return match ($type) {
            ExportType::PDF->value => $this->exportToPdf($project, $artistResidencies, $language),
            default => $this->exportToExcel($project, $artistResidencies, $language),
        };
    }

    /**
     * Retrieves artist residencies by project ID.
     */
    public function getArtistResidenciesByProjectId(int $projectId): Collection
    {
        return $this->artistResidencyRepository->getArtistResidencyByProjectId($projectId);
    }

    /**
     * Exports data to PDF format.
     */
    private function exportToPdf(Project $project, Collection $artistResidencies, string $language): Response
    {
        $pdfContent = $this->pdf->loadView(
            'pdf.artist-residency-per-diem',
            [
                'artistResidencies' => $artistResidencies,
                'project' => $project->load(['costCenter']),
                'user' => $this->authManager->user(),
                'language' => $language,
            ]
        )->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 72,
                'defaultFont' => 'sans-serif',
            ]);

        $this->ensureDirectoryExists('pdf');

        $filename = $this->createFilename(now(), $project->name, '72');
        $pdfContent->save($this->createStoragePath($filename));

        return $this->inertiaResponseFactory->location(
            route('artist-residency.export.pdf.download', ['filename' => $filename])
        );
    }

    /**
     * Exports data to Excel format.
     */
    private function exportToExcel(
        Project $project,
        Collection $artistResidencies,
        string $language
    ): BinaryFileResponse {
        $filename = sprintf(
            'per_diem_export_%s_stand_%s.xlsx',
            $project->name,
            now()->format('d-m-Y_H_i_s')
        );

        return (new ArtistResidencyExcelExport(
            $artistResidencies,
            $project->load(['costCenter']),
            $language
        ))
            ->download($filename)
            ->deleteFileAfterSend();
    }

    /**
     * Ensures the directory exists, creating it if necessary.
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if ($this->filesystemManager->directoryMissing($directory)) {
            $this->filesystemManager->makeDirectory($directory);
        }
    }

    /**
     * Creates a file path for storage.
     */
    private function createStoragePath(string $filename): string
    {
        return $this->filesystemManager->path('pdf/' . $filename);
    }

    /**
     * Handles PDF file download.
     */
    public function downloadPdf(string $filename): BinaryFileResponse
    {
        return $this->responseFactory->download(
            $this->createStoragePath($filename)
        )->deleteFileAfterSend();
    }

    /**
     * Generates a filename based on the provided parameters.
     */
    public function createFilename(Carbon $carbon, string $title, string $dpi): string
    {
        return sprintf(
            '%s_%s_dpi_%s.pdf',
            $carbon->format('d.m.Y-H:i:s'),
            str_replace(' ', '_', $title),
            $dpi
        );
    }
}
