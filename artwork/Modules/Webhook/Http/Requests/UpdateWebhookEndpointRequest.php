<?php

namespace Artwork\Modules\Webhook\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Rechteprüfung passiert im Controller über die WebhookEndpointPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => ['required', 'url:https', 'max:255'],
            'subscribed_events' => 'required|array|min:1',
            'subscribed_events.*' => ['string', Rule::in(array_keys(config('webhooks.events', [])))],
            'is_active' => 'boolean',
        ];
    }
}
