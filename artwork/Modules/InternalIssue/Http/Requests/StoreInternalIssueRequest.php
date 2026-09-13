<?php

namespace Artwork\Modules\InternalIssue\Http\Requests;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Illuminate\Foundation\Http\FormRequest;

class StoreInternalIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autorisierung VOR der Validierung, sonst antwortet ein fehlendes Recht
        // mit einem Validierungs-Redirect (302) statt 403.
        $projectId = $this->integer('project_id') ?: null;

        return $this->user()?->can('create', [InternalIssue::class, $projectId]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'room_id' => 'nullable|exists:rooms,id',
            'notes' => 'nullable|string',
            'responsible_user_ids' => 'nullable|array',
            'responsible_user_ids.*' => 'integer|exists:users,id',
            'special_items_done' => 'boolean',
            'files.*' => 'file|max:20480', // 20 MB pro Datei
            'special_items' => 'nullable|array',
            'special_items.*.name' => 'required|string|max:255',
            'special_items.*.quantity' => 'required|integer|min:1',
            'special_items.*.description' => 'nullable|string',
            'special_items.*.inventory_category_id' => 'nullable|exists:inventory_categories,id',
            'special_items.*.inventory_sub_category_id' => 'nullable|exists:inventory_sub_categories,id',
            'articles' => 'nullable|array',
            'articles.*.id' => ['required', \Illuminate\Validation\Rule::exists('inventory_articles', 'id')->whereNull('deleted_at')],
            'articles.*.quantity' => 'required|integer|min:1',
        ];
    }
}
