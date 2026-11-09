<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Was beim Live-Schalten aus dem Testmodus bleibt; das Verkaufte geht immer. */
class GoLiveRequest extends FormRequest
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
            'keep_shop_look' => 'required|boolean',
            'keep_pass_types' => 'required|boolean',
            'keep_coupons' => 'required|boolean',
            'keep_dates' => 'required|boolean',
        ];
    }
}
