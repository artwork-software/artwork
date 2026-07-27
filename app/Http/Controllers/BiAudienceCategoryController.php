<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Mandantenweite Pflege der Besucher*innen-Kategorien
 * (Settings → BI, Route-Gruppe ist per 'change tool settings' gegated).
 */
class BiAudienceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BiAudienceCategory::ordered()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pricing_type' => ['required', Rule::in(['full', 'reduced', 'free'])],
        ]);

        $category = BiAudienceCategory::create([
            'name' => $validated['name'],
            'pricing_type' => $validated['pricing_type'],
            'position' => ((int) BiAudienceCategory::max('position')) + 1,
            'is_active' => true,
        ]);

        $this->bumpDashboardCacheVersion();

        return response()->json($category, 201);
    }

    public function update(Request $request, BiAudienceCategory $biAudienceCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'pricing_type' => ['sometimes', 'required', Rule::in(['full', 'reduced', 'free'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $biAudienceCategory->update($validated);
        $this->bumpDashboardCacheVersion();

        return response()->json($biAudienceCategory->fresh());
    }

    public function destroy(BiAudienceCategory $biAudienceCategory): JsonResponse
    {
        // Kategorien mit erfassten Werten nicht löschen (historische Zahlen!),
        // sondern deaktivieren — sonst verschwinden Summen rückwirkend.
        if ($biAudienceCategory->values()->exists()) {
            return response()->json([
                'message' => __('This category has recorded values and can only be deactivated.'),
            ], 422);
        }

        $biAudienceCategory->delete();
        $this->bumpDashboardCacheVersion();

        return response()->json(null, 204);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:bi_audience_categories,id'],
        ]);

        foreach ($request->input('ordered_ids') as $index => $id) {
            BiAudienceCategory::where('id', $id)->update(['position' => $index]);
        }

        return response()->json(BiAudienceCategory::ordered()->get());
    }

    private function bumpDashboardCacheVersion(): void
    {
        Cache::increment('bi_dashboard_version');
    }
}
