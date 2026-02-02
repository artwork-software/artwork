<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\PdfExportUserFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfExportUserFilterController extends Controller
{
    public function index(): JsonResponse
    {
        $filters = PdfExportUserFilter::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json($filters);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['nullable', 'array'],
        ]);

        $filter = PdfExportUserFilter::query()->create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'filters' => $data['filters'] ?? [],
        ]);

        return response()->json([
            'ok' => true,
            'filter' => $filter,
        ]);
    }

    public function destroy(PdfExportUserFilter $pdfExportUserFilter): JsonResponse
    {
        abort_unless($pdfExportUserFilter->user_id === auth()->id(), 403);

        $pdfExportUserFilter->delete();

        return response()->json(['ok' => true]);
    }
}
