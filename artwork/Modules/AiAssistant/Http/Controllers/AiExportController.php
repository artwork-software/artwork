<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Models\AiExport;
use Artwork\Modules\AiAssistant\Services\AiExportService;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download eines KI-Exports aus der Bar (core-ai-event-stats-exports). Nur die Person, die den Export
 * erzeugt hat; fremde oder abgelaufene Exporte sind 404 — kein Hinweis darauf, dass es sie gibt.
 */
class AiExportController extends Controller
{
    public function download(Request $request, AiExport $export, FilesystemManager $files): BinaryFileResponse
    {
        abort_unless(
            $export->user_id === $request->user()?->id
                && $export->expires_at->isFuture()
                && $files->exists($export->storagePath()),
            404
        );

        $format = $export->format();

        return response()->download(
            $files->path($export->storagePath()),
            AiExportService::filename($export->title, $format),
            ['Content-Type' => AiExportService::MIME_TYPES[$format]]
        );
    }
}
