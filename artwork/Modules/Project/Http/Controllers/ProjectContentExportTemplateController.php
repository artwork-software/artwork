<?php

namespace Artwork\Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Http\Requests\ProjectContentExportTemplateRequest;
use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Vorlagen des Projektinhalte-Exports: für alle mit Exportrecht sichtbar und nutzbar, ändern und löschen nur
 * die anlegende Person oder Admins (ProjectContentExportTemplatePolicy).
 */
class ProjectContentExportTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = ProjectContentExportTemplate::query()->orderBy('name')->get();
        $memberIds = $templates
            ->flatMap(fn (ProjectContentExportTemplate $template) => $template->settings['filters']['member_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $members = User::query()
            ->whereKey($memberIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        return response()->json(
            $templates->map(fn (ProjectContentExportTemplate $template) => $this->serialize(
                $template,
                $request->user(),
                $members
            ))->values()
        );
    }

    public function store(ProjectContentExportTemplateRequest $request): JsonResponse
    {
        $template = ProjectContentExportTemplate::create([
            ...$request->templateData(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($this->serialize($template, $request->user()));
    }

    public function update(
        ProjectContentExportTemplateRequest $request,
        ProjectContentExportTemplate $projectContentExportTemplate
    ): JsonResponse {
        $this->authorize('update', $projectContentExportTemplate);
        $projectContentExportTemplate->update($request->templateData());

        return response()->json($this->serialize($projectContentExportTemplate->fresh(), $request->user()));
    }

    public function destroy(ProjectContentExportTemplate $projectContentExportTemplate): JsonResponse
    {
        $this->authorize('delete', $projectContentExportTemplate);
        $projectContentExportTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * @param Collection<int, User>|null $members
     * @return array<string, mixed>
     */
    private function serialize(ProjectContentExportTemplate $template, User $user, ?Collection $members = null): array
    {
        $memberIds = array_map('intval', $template->settings['filters']['member_ids'] ?? []);
        $members ??= User::query()->whereKey($memberIds)->get(['id', 'first_name', 'last_name'])->keyBy('id');

        return [
            'id' => $template->id,
            'name' => $template->name,
            'columns' => $template->columns,
            'settings' => $template->settings ?? [],
            // Für die Personen-Tags im Modal; gelöschte Personen fallen weg
            'members' => collect($memberIds)
                ->map(fn (int $id) => $members->get($id))
                ->filter()
                ->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => trim($member->first_name . ' ' . $member->last_name),
                ])
                ->values(),
            'created_by' => $template->created_by,
            'can_manage' => $user->can('update', $template),
        ];
    }
}
