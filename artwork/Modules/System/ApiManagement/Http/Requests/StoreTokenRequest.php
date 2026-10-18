<?php

namespace Artwork\Modules\System\ApiManagement\Http\Requests;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Illuminate\Foundation\Http\FormRequest;
use Artwork\Modules\System\ApiManagement\Support\MachineScopes;
use Illuminate\Validation\Rule;

class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Rechteprüfung passiert im Controller über die TokenPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            // Mindestens ein Scope: Ein Token ohne Scopes käme durch keine Prüfung der Maschinen-API
            // und wäre damit nutzlos.
            'scopes' => 'required|array|min:1',
            // ai:*-Scopes sind hier nicht wählbar: ein Kunden-Key lebt ein Jahr und hinge an keiner
            // Session — damit ließe sich der KI-Dienst ohne Session-Bindung ansprechen (Konzept C2 §3).
            'scopes.*' => ['string', Rule::in(AiScopes::excludeFrom(Passport::scopeIds()))],
            'scopes.*' => ['string', Rule::in(MachineScopes::ids())],
        ];
    }
}
