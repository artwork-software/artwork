<?php

namespace Artwork\Modules\Crm\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * CRM-Einstellung darf so nicht geändert/gelöscht werden (Systemeintrag, noch zugeordnete Kontakte).
 * Vorher RuntimeException → 500; jetzt 422-JSON bzw. Flash-Fehler für Inertia.
 */
class CrmSettingLockedException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
