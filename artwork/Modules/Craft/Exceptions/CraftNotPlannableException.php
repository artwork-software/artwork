<?php

namespace Artwork\Modules\Craft\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Die Person darf das Gewerk nicht planen (nicht als Gewerksplaner:in eingetragen, Gewerk nicht
 * "für alle planbar"). Dieselbe Meldung für axios (403-JSON) und Inertia (Flash-Toast).
 */
class CraftNotPlannableException extends RuntimeException
{
    /**
     * @param array<int, string> $craftNames
     */
    public function __construct(public readonly array $craftNames)
    {
        parent::__construct(
            $craftNames === []
                ? __('You are currently not registered as a shift planner for this craft.')
                : __(
                    'You are currently not registered as a shift planner for the craft ":crafts".',
                    ['crafts' => implode(', ', $craftNames)]
                )
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => ['craft_id' => [$this->getMessage()]],
            ], 403);
        }

        return back()->with('error', $this->getMessage());
    }
}
