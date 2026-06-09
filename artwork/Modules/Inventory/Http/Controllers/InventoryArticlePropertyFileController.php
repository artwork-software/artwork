<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Http\Requests\UploadInventoryPropertyFileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles single-file uploads for inventory article properties of type "file".
 *
 * The stored relative path is returned to the frontend and kept as the property
 * value (inventory_property_values.value, varchar(255)). The article-save flow
 * then persists that path like any other property value – no extra table needed.
 */
class InventoryArticlePropertyFileController extends Controller
{
    private const BASE_DIR = 'uploads/inventory-properties';

    public function upload(UploadInventoryPropertyFileRequest $request): JsonResponse
    {
        $file = $request->file('file');

        // Unique uuid sub-folder keeps the original (sanitized) name human readable
        // while avoiding collisions between articles using the same file name.
        $dir = self::BASE_DIR . '/' . Str::uuid()->toString();
        $name = $this->sanitizeName($file->getClientOriginalName());

        $path = $file->storeAs($dir, $name);

        return response()->json([
            'path' => $path,
            'name' => $name,
            'url' => route('inventory-management.articles.property-file.download', ['path' => $path]),
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $path = $this->validatedPath($request);

        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, basename($path));
    }

    public function destroy(Request $request): JsonResponse
    {
        $path = $this->validatedPath($request);

        if (Storage::exists($path)) {
            Storage::delete($path);
            // Remove the now-empty uuid folder so storage doesn't accumulate dirs.
            Storage::deleteDirectory(dirname($path));
        }

        return response()->json(['deleted' => true]);
    }

    /**
     * Resolve and harden the path from the request to prevent traversal or
     * access to files outside the property uploads directory.
     */
    private function validatedPath(Request $request): string
    {
        $path = (string) ($request->input('path') ?? $request->query('path', ''));

        abort_unless(
            $path !== ''
            && str_starts_with($path, self::BASE_DIR . '/')
            && !str_contains($path, '..'),
            403
        );

        return $path;
    }

    /**
     * Keep a filesystem/URL friendly name, preserve the extension and cap the
     * length so the full relative path always fits into the value column (255).
     */
    private function sanitizeName(string $name): string
    {
        $name = basename($name);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);

        $base = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '_', $base) ?: 'file';
        $base = trim(substr($base, 0, 150));

        return $extension !== '' ? $base . '.' . strtolower($extension) : $base;
    }
}
