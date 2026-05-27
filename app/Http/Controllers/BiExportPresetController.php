<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiExportPresetController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeBiExport();

        return response()->json(
            BiExportPreset::query()->orderBy('name')->get(['id', 'name', 'columns', 'is_shared'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeBiExport();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
        ]);

        $preset = BiExportPreset::create([
            'name' => $validated['name'],
            'columns' => $validated['columns'],
            'created_by' => $request->user()->id,
            'is_shared' => true,
        ]);

        return response()->json($preset->only(['id', 'name', 'columns', 'is_shared']));
    }

    public function destroy(BiExportPreset $biExportPreset): JsonResponse
    {
        $this->authorizeBiExport();

        $biExportPreset->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeBiExport(): void
    {
        abort_unless(
            auth()->user()->can(PermissionEnum::BI_EXPORT->value) || auth()->user()->hasRole('admin'),
            403
        );
    }
}
