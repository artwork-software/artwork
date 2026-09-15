<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiConversationTurnRequest extends FormRequest
{
    /** Konzept C3 §9. */
    public const MAX_USER_MESSAGE_CHARS = 4000;
    public const MAX_ASSISTANT_MESSAGE_BYTES = 65536;
    public const MAX_TOOL_CALLS = 50;

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
            'client_turn_id' => ['required', 'uuid'],
            'user_message' => ['required', 'string', 'max:' . self::MAX_USER_MESSAGE_CHARS],
            'assistant_message' => ['nullable', 'string'],
            'status' => ['required', Rule::in([
                AiConversationTurn::STATUS_COMPLETED,
                AiConversationTurn::STATUS_CANCELLED,
                AiConversationTurn::STATUS_ERROR,
                AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            ])],
            'error_code' => ['nullable', 'string', 'max:255'],
            'tool_calls' => ['sometimes', 'array', 'max:' . self::MAX_TOOL_CALLS],
            'tool_calls.*.name' => ['required', 'string', 'max:255'],
            'tool_calls.*.label' => ['required', 'string', 'max:255'],
            'tool_calls.*.status' => ['required', 'string', 'max:255'],
            'context' => ['sometimes', 'array'],
            'context.route' => ['nullable', 'string', 'max:255'],
            'context.project_id' => ['nullable', 'integer'],
            'context.locale' => ['nullable', 'string', 'max:16'],
            'context.artwork_version' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // 64 KB sind eine Byte-Grenze, keine Zeichengrenze — 'max' auf einem String zaehlt
            // in Laravel Zeichen, was bei Mehrbyte-Zeichen zu wenig abweist.
            $assistantMessage = $this->input('assistant_message');
            if (is_string($assistantMessage) && strlen($assistantMessage) > self::MAX_ASSISTANT_MESSAGE_BYTES) {
                $validator->errors()->add('assistant_message', __('The assistant message is too long.'));
            }

            // pending_action gehoert zu W2 und darf in v1 nicht gesetzt werden.
            if ($this->has('pending_action')) {
                $validator->errors()->add('pending_action', __('This field is not supported yet.'));
            }
        });
    }
}
