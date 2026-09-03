<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Export-Vorlagen sind für alle sichtbar (Produktentscheidung 03.09.2026: nur geteilt);
 * löschen und ändern darf nur, wer sie angelegt hat, oder ein Admin.
 */
class BiExportPresetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeBiExport($request);

        return response()->json(
            BiExportPreset::query()
                ->orderBy('name')
                ->get(['id', 'name', 'columns', 'is_shared', 'created_by'])
                ->map(fn (BiExportPreset $preset) => $this->serialize($preset, $request))
                ->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeBiExport($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('bi_export_presets', 'name')],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in(BiExportService::allowedColumnKeys())],
        ], [
            'name.unique' => __('A preset with this name already exists.'),
        ]);

        $preset = BiExportPreset::create([
            'name' => $validated['name'],
            'columns' => BiExportService::orderColumns($validated['columns']),
            'created_by' => $request->user()->id,
            'is_shared' => true,
        ]);

        return response()->json($this->serialize($preset, $request));
    }

    public function update(Request $request, BiExportPreset $biExportPreset): JsonResponse
    {
        $this->authorizeBiExport($request);
        $this->authorizeManage($request, $biExportPreset);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('bi_export_presets', 'name')->ignore($biExportPreset->id)],
            'columns' => ['sometimes', 'required', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in(BiExportService::allowedColumnKeys())],
        ], [
            'name.unique' => __('A preset with this name already exists.'),
        ]);

        if (array_key_exists('columns', $validated)) {
            $validated['columns'] = BiExportService::orderColumns($validated['columns']);
        }

        $biExportPreset->update($validated);

        return response()->json($this->serialize($biExportPreset->fresh(), $request));
    }

    public function destroy(Request $request, BiExportPreset $biExportPreset): JsonResponse
    {
        $this->authorizeBiExport($request);
        $this->authorizeManage($request, $biExportPreset);

        $biExportPreset->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(BiExportPreset $preset, Request $request): array
    {
        return [
            'id' => $preset->id,
            'name' => $preset->name,
            'columns' => $preset->columns,
            'is_shared' => $preset->is_shared,
            'created_by' => $preset->created_by,
            'can_manage' => $this->canManage($request, $preset),
        ];
    }

    private function canManage(Request $request, BiExportPreset $preset): bool
    {
        $user = $request->user();

        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value) || $preset->created_by === $user->id;
    }

    private function authorizeManage(Request $request, BiExportPreset $preset): void
    {
        abort_unless($this->canManage($request, $preset), 403, __('Only the creator or an admin can change this preset.'));
    }

    private function authorizeBiExport(Request $request): void
    {
        abort_unless(
            $request->user()->can(PermissionEnum::BI_EXPORT->value)
                || $request->user()->hasRole(RoleEnum::ARTWORK_ADMIN->value),
            403
        );
    }
}
