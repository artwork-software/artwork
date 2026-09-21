<?php

namespace App\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\LinkListTemplate;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkListTemplateController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager
    ) {
    }

    /**
     * Get all link list templates.
     */
    public function index(): JsonResponse
    {
        $templates = LinkListTemplate::orderBy('name')->get();

        return response()->json($templates);
    }

    /**
     * Store a new link list template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entries' => 'required|array',
            'entries.*.display' => 'required|string',
        ]);

        $template = LinkListTemplate::create([
            'name' => $validated['name'],
            'entries' => $validated['entries'],
            'created_by' => $this->authManager->id(),
        ]);

        return response()->json($template, 201);
    }

    /**
     * Update an existing link list template.
     */
    public function update(Request $request, LinkListTemplate $linkListTemplate): JsonResponse
    {
        $this->authorizeTemplateChange($linkListTemplate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entries' => 'required|array',
            'entries.*.display' => 'required|string',
        ]);

        $linkListTemplate->update([
            'name' => $validated['name'],
            'entries' => $validated['entries'],
        ]);

        return response()->json($linkListTemplate);
    }

    /**
     * Delete a link list template.
     */
    public function destroy(LinkListTemplate $linkListTemplate): JsonResponse
    {
        $this->authorizeTemplateChange($linkListTemplate);

        $linkListTemplate->delete();

        return response()->json(null, 204);
    }

    /**
     * Vorlagen sind global (alle Projekte). Anlegen darf jede angemeldete Person (aus der Link-Listen-Komponente),
     * ändern/löschen nur die Ersteller*in oder wer Projekt-Einstellungen verwalten darf.
     */
    private function authorizeTemplateChange(LinkListTemplate $linkListTemplate): void
    {
        $user = $this->authManager->user();
        abort_unless(
            $user !== null
            && ((int) $linkListTemplate->created_by === (int) $user->getAuthIdentifier()
                || $user->can(PermissionEnum::PROJECT_SETTINGS_UPDATE->value)),
            403
        );
    }
}
