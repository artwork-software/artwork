<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Eine Produktionsgruppe aus dem Tab "Karten & Abos": Name und die Produktionen darin. */
class SaveTicketingProductionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Berechtigung liegt auf der Routengruppe (manage ticketing).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:60',
            'eventIds' => 'present|array|max:500',
            'eventIds.*' => 'uuid',
        ];
    }
}
