<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Services\AiEventWriteService;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ein Turn, wie artwork-ai ihn schreibt (core-chat-historie; ab W2 optional mit `pending_action`).
 *
 * **Keine `required`-Regel unterhalb von `pending_action`**: scramble markiert ein Objekt als
 * Pflichtfeld, sobald eine einzige verschachtelte Regel `required` traegt
 * (`DeepParametersMerger.php:89-93`). `pending_action` ist aber optional und ausserhalb von
 * `awaiting_confirmation` sogar verboten — ein Fall-A-Turn koennte die exportierte Spec sonst nie
 * erfuellen. Die Pflichtfelder innerhalb der Vorschau prueft deshalb `validatePendingAction()`.
 */
class StoreAiConversationTurnRequest extends FormRequest
{
    /** Konzept C3 §9. */
    public const MAX_USER_MESSAGE_CHARS = 4000;
    public const MAX_ASSISTANT_MESSAGE_BYTES = 65536;
    public const MAX_TOOL_CALLS = 50;

    /** Grenzen der Vorschau aus `sse-events/confirm.request.schema.json`. */
    public const MAX_PREVIEW_SUMMARY_CHARS = 500;
    public const MAX_PREVIEW_ITEMS = 200;
    public const MAX_PREVIEW_LABEL_CHARS = 200;

    /** Werkzeuge, zu denen es eine bestaetigungspflichtige Aktion geben kann (Konzept W1 §1). */
    public const CONFIRMABLE_TOOLS = [
        AiEventWriteService::TOOL_CREATE,
        AiEventWriteService::TOOL_UPDATE,
        AiEventWriteService::TOOL_DELETE,
    ];

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

            // Bestaetigungspflichtige Aktion (core-confirm-actions; Konzept W2 §1). Der Turn ist
            // die Aktion — deshalb steht sie hier und nicht in einer eigenen Tabelle.
            'pending_action' => ['sometimes', 'array'],
            'pending_action.tool' => ['required_with:pending_action', Rule::in(self::CONFIRMABLE_TOOLS)],
            // `@var` uebersteuert das Schema im Export: eine Regel `array` ohne Unterregeln wird
            // sonst zu `array<string>`, `args` ist aber ein Objekt mit werkzeugabhaengigen
            // Schluesseln (contracts/action-contract.md). Export-Fix 2, 16.09.2026.
            /** @var object */
            'pending_action.args' => ['required_with:pending_action', 'array'],
            'pending_action.args_hash' => ['required_with:pending_action', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            // Form der Vorschau ist bindend: contracts/action-contract.md verweist auf
            // sse-events/confirm.request.schema.json — summary ist ein Text, items tragen
            // action/label und optional Konflikte als Texte.
            'pending_action.preview' => ['required_with:pending_action', 'array'],
            'pending_action.preview.summary' => [
                'required_with:pending_action.preview',
                'string',
                'max:' . self::MAX_PREVIEW_SUMMARY_CHARS,
            ],
            // 'sometimes' statt 'required_with': eine leere Liste ist gueltig (Vorschau ohne
            // Objekte), wuerde von required_with aber als „fehlt" gewertet. Dass der Schluessel
            // ueberhaupt da ist, prueft validatePendingAction().
            'pending_action.preview.items' => ['sometimes', 'array', 'max:' . self::MAX_PREVIEW_ITEMS],
            // 'sometimes' statt 'required': siehe Klassenkommentar. Dass beide Felder da sind,
            // prueft validatePendingAction() und meldet es am exakt gleichen Schluessel.
            'pending_action.preview.items.*.action' => ['sometimes', Rule::in(['create', 'update', 'delete'])],
            'pending_action.preview.items.*.label' => ['sometimes', 'string', 'max:' . self::MAX_PREVIEW_LABEL_CHARS],
            'pending_action.preview.items.*.conflicts' => ['sometimes', 'array'],
            'pending_action.preview.items.*.conflicts.*' => ['string', 'max:' . self::MAX_PREVIEW_LABEL_CHARS],
            'pending_action.expires_at' => ['required_with:pending_action', 'date'],
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

            $this->validatePendingAction($validator);
        });
    }

    /**
     * Die Aktion muss zum Status passen, in die Zukunft zeigen und ihren eigenen Fingerabdruck
     * korrekt tragen — artwork rechnet den Hash nach und glaubt ihn nicht (Konzept W2 §4).
     */
    private function validatePendingAction(Validator $validator): void
    {
        $isAwaiting = $this->input('status') === AiConversationTurn::STATUS_AWAITING_CONFIRMATION;
        $action = $this->input('pending_action');

        if (!is_array($action)) {
            if ($isAwaiting) {
                $validator->errors()->add('pending_action', __('This field is required.'));
            }

            return;
        }

        if (!$isAwaiting) {
            $validator->errors()->add('pending_action', __('This field is not supported yet.'));

            return;
        }

        $this->validatePreview($validator, $action['preview'] ?? null);

        $this->validateArgsHash($validator, $action);
        $this->validateExpiry($validator, $action);
    }

    /**
     * Die Vorschau hat die Form aus `sse-events/confirm.request.schema.json`: `summary` und
     * `items`, jedes Element mit `action` und `label` (contracts/action-contract.md).
     *
     * @param mixed $preview
     */
    private function validatePreview(Validator $validator, mixed $preview): void
    {
        if (!is_array($preview)) {
            return;
        }

        if (!array_key_exists('items', $preview)) {
            $validator->errors()->add('pending_action.preview.items', __('This field is required.'));

            return;
        }

        if (!is_array($preview['items'])) {
            return;
        }

        foreach ($preview['items'] as $index => $item) {
            foreach (['action', 'label'] as $field) {
                if (!is_array($item) || ($item[$field] ?? null) === null) {
                    $validator->errors()->add(
                        "pending_action.preview.items.{$index}.{$field}",
                        __('This field is required.')
                    );
                }
            }
        }
    }

    /**
     * Der Hash wird nachgerechnet, nicht geglaubt (contracts/action-contract.md).
     *
     * @param array<string, mixed> $action
     */
    private function validateArgsHash(Validator $validator, array $action): void
    {
        $args = $action['args'] ?? null;
        $hash = $action['args_hash'] ?? null;

        if (is_array($args) && is_string($hash) && !AiActionHash::matches($args, $hash)) {
            $validator->errors()->add('pending_action.args_hash', __('The hash does not match the arguments.'));
        }
    }

    /**
     * Die Bestaetigungsfrist liegt in der Zukunft und hoechstens `ai.action_ttl_minutes` entfernt.
     *
     * @param array<string, mixed> $action
     */
    private function validateExpiry(Validator $validator, array $action): void
    {
        $expiresAt = $action['expires_at'] ?? null;

        if (!is_string($expiresAt)) {
            return;
        }

        $maxMinutes = (int) config('ai.action_ttl_minutes');
        $expires = CarbonImmutable::parse($expiresAt);

        if ($expires->isPast() || $expires->greaterThan(CarbonImmutable::now()->addMinutes($maxMinutes))) {
            $validator->errors()->add(
                'pending_action.expires_at',
                __('The confirmation window must be in the future and at most :minutes minutes.', [
                    'minutes' => $maxMinutes,
                ])
            );
        }
    }
}
