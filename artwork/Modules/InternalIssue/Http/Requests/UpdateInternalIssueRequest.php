<?php

namespace Artwork\Modules\InternalIssue\Http\Requests;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInternalIssueRequest extends FormRequest
{
    use ValidatesInternalIssuePeriod;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $issue = $this->route('internalIssue');

        if (!$issue instanceof InternalIssue || !($this->user()?->can('update', $issue) ?? false)) {
            return false;
        }

        // Projektwechsel nur, wenn im Zielprojekt angelegt werden dürfte.
        $newProjectId = $this->integer('project_id') ?: null;

        if ($newProjectId !== null && $newProjectId !== (int) $issue->project_id) {
            return $this->user()->can('create', [InternalIssue::class, $newProjectId]);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:internal_issues,id',
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'room_id' => 'nullable|exists:rooms,id',
            'notes' => 'nullable|string',
            'responsible_user_ids' => 'nullable|array',
            'responsible_user_ids.*' => 'integer|exists:users,id',
            'special_items_done' => 'boolean',
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt,zip', 'max:20480', new SafeUploadFile()],
            'special_items' => 'nullable|array',
            'special_items.*.name' => 'required|string|max:255',
            'special_items.*.quantity' => 'required|integer|min:1',
            'special_items.*.description' => 'nullable|string',
            'special_items.*.inventory_category_id' => 'nullable|exists:inventory_categories,id',
            'special_items.*.inventory_sub_category_id' => 'nullable|exists:inventory_sub_categories,id',
            'articles' => 'nullable|array',
            'articles.*.id' => [
                'required',
                // Doppelte Einträge überschrieben sich beim Speichern (letzter gewinnt, Mengen gingen verloren)
                'distinct',
                // Bereits verknüpfte Artikel dürfen inzwischen im Papierkorb liegen (bleiben Teil der Ausgabe)
                \Illuminate\Validation\Rule::exists('inventory_articles', 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')
                        ->orWhereIn('id', $this->attachedArticleIds())),
            ],
            'articles.*.quantity' => 'required|integer|min:1',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function attachedArticleIds(): array
    {
        $issue = $this->route('internalIssue');
        if ($issue === null) {
            return [];
        }

        return $issue->articles()->pluck('inventory_articles.id')->map(fn ($id): int => (int) $id)->all();
    }
}
