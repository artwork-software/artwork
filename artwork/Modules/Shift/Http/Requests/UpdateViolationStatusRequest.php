<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateViolationStatusRequest extends FormRequest
{
    /**
     * Dieselbe Permission, die die Oberflaeche fuer genau diese Aktion verlangt: die Web-Routen
     * shift-rule-violations.resolve und .ignore (routes/web.php) liegen hinter
     * can:can plan shifts. Die Route traegt die Pruefung ebenfalls — hier steht sie, damit sie
     * ein Verschieben der Route ueberlebt.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SHIFT_PLANNER->value) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:resolved,ignored',
        ];
    }
}
