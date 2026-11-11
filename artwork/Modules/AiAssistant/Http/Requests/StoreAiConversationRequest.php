<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiConversationRequest extends FormRequest
{
    /** Der Zugriff haengt am Token-Scope ai:transcript (CheckToken auf der Route). */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
