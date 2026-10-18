<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => 'required|string|max:5000',
            'project_id' => 'required|integer|exists:projects,id',
            'tab_id' => 'nullable|integer|exists:project_tabs,id',
        ];
    }

    /**
     * Kommentare nur in Tabs, die die Person sehen darf – vorher ließ sich blind in versteckte Tabs
     * kommentieren (Admins passieren über canSeeTab).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('tab_id') || !$this->filled('tab_id')) {
                return;
            }

            $user = $this->user();
            if (
                !$user instanceof User
                || !app(ProjectComponentVisibilityService::class)->canSeeTab($user, $this->integer('tab_id'))
            ) {
                $validator->errors()->add('tab_id', __('You do not have permission to access this project tab.'));
            }
        });
    }
}
