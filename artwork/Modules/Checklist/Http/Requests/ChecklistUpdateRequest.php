<?php

namespace Artwork\Modules\Checklist\Http\Requests;

use Artwork\Modules\Event\Http\Requests\EventStoreOrUpdateRequest;

/**
 * Sicherheits-Audit 21.09.2026 (E, HOCH): Die Checkliste wurde per fill($request->all()) befüllt,
 * fillable waren project_id/user_id/tab_id. Jetzt gibt es eine feste Feldliste (fillableFields())
 * und user_id ist nicht mehr Teil des Requests. Projekt-/Tab-Wechsel werden im Controller
 * autorisiert (createProperties auf dem Zielprojekt, Tab = globale Projekttab-Definition).
 *
 * FALLE: kein data()-Override mehr — filled()/boolean()/integer() lesen über data(), ein Override
 * auf only([...]) machte project_id/tab_id für den Controller unsichtbar.
 */
class ChecklistUpdateRequest extends EventStoreOrUpdateRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'private' => ['sometimes', 'boolean'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'tab_id' => ['sometimes', 'nullable', 'integer', 'exists:project_tabs,id'],
            'tasks' => ['sometimes', 'array'],
            'tasks.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tasks.*.description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'tasks.*.done' => ['required', 'nullable', 'boolean'],
            'tasks.*.order' => ['required', 'nullable', 'int'],
            'tasks.*.deadline' => ['sometimes', 'nullable', 'date'],

            'assigned_department_ids' => [
                'sometimes',
                'array',
            ],
            'assigned_department_ids.*.*' => ['required', 'exists:departments,id'],
            'assigned_user_ids' => ['sometimes', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Felder, die direkt auf das Modell geschrieben werden dürfen. user_id bleibt immer beim Ersteller;
     * project_id/tab_id gehen nur nach expliziter Autorisierung im Controller auf das Modell.
     *
     * @return array<string, mixed>
     */
    public function fillableFields(): array
    {
        return $this->only(['name', 'private']);
    }
}
