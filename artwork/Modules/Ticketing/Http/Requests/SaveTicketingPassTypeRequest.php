<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Eine Mehrfachkarte oder ein Abo aus dem Tab "Karten & Abos", in der Form, die tickets erwartet:
 * Beträge in Cent, Tage als Y-m-d, "Gültig bis" als Uhrzeit des Hauses. Was nur tickets prüfen
 * kann (Saisonlänge, Verkauftes bleibt fest), kommt von dort als Meldung zurück.
 */
class SaveTicketingPassTypeRequest extends FormRequest
{
    /** Der Zahlungsdienstleister bucht nichts unter 0,50 €. */
    private const MIN_SUBSCRIPTION_PRICE_CENTS = 50;

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
            'kind' => ['required', Rule::in(['card', 'subscription'])],
            'name' => 'required|string|min:2|max:120',
            'description' => 'nullable|string|max:4000',
            'price' => 'nullable|integer|min:0',
            'uses' => 'required|integer|min:1|max:1000',
            'monthlyPrice' => 'nullable|integer|min:' . self::MIN_SUBSCRIPTION_PRICE_CENTS,
            'upfrontPrice' => 'nullable|integer|min:' . self::MIN_SUBSCRIPTION_PRICE_CENTS,
            'termMode' => ['required', Rule::in(['open', 'months', 'season'])],
            'termMonths' => 'nullable|integer|min:1|max:24',
            'seasonEnds' => 'nullable|date_format:Y-m-d',
            'saleEnds' => 'nullable|date_format:Y-m-d',
            'afterTerm' => ['required', Rule::in(['ends', 'monthly'])],
            'usesWindow' => ['required', Rule::in(['month', 'term'])],
            'rolloverMax' => 'required|integer|min:0|max:100',
            'taxTreatment' => ['nullable', Rule::in(['exempt', 'reduced', 'standard'])],
            'validityDays' => 'nullable|integer|min:1|max:3650',
            'validUntil' => 'nullable|date_format:Y-m-d\TH:i',
            'discountKind' => ['required', Rule::in(['percent', 'fixed'])],
            'discountPercent' => 'nullable|required_if:discountKind,percent|integer|min:1|max:100',
            'discountAmount' => 'nullable|required_if:discountKind,fixed|integer|min:1',
            'maxUsesPerOrder' => 'nullable|integer|min:1|max:100',
            'maxUsesPerEvent' => 'nullable|integer|min:1|max:1000',
            'maxUsesPerPeriod' => 'nullable|integer|min:1|max:100',
            'limitPeriod' => ['required', Rule::in(['day', 'week', 'month'])],
            'redeemableInCheckout' => 'required|boolean',
            'stampable' => 'required|boolean',
            'allEvents' => 'required|boolean',
            'eventIds' => 'present|array|max:200',
            'eventIds.*' => 'uuid',
            'groupIds' => 'present|array|max:50',
            'groupIds.*' => 'uuid',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('kind') === 'card') {
                if ($this->input('price') === null) {
                    $validator->errors()->add('price', __('A pass needs a price.'));
                }
            } else {
                $this->checkSubscription($validator);
            }

            if ($this->input('validityDays') !== null && $this->input('validUntil') !== null) {
                $validator->errors()->add('validUntil', __('Either days from purchase or a fixed date, not both.'));
            }
        });
    }

    /**
     * Dieselben Regeln wie das Formular in Artwork-Tickets: welche Preise zu welcher Laufzeit passen.
     */
    private function checkSubscription(Validator $validator): void
    {
        $fail = static fn (string $field, string $message) => $validator->errors()->add($field, $message);
        $monthly = $this->input('monthlyPrice');
        $upfront = $this->input('upfrontPrice');
        $termMonths = $this->input('termMonths');

        if ($monthly === null && $upfront === null) {
            $fail('monthlyPrice', __('A subscription needs a monthly or a one-off price.'));
        }

        switch ($this->input('termMode')) {
            case 'open':
                if ($monthly === null) {
                    $fail('monthlyPrice', __('An open-ended subscription is paid monthly.'));
                }
                if ($upfront !== null) {
                    $fail('upfrontPrice', __('An open-ended subscription has no one-off price.'));
                }
                if ($this->input('usesWindow') === 'term') {
                    $fail('usesWindow', __('Uses over the whole term need a fixed term.'));
                }
                break;

            case 'months':
                if ($termMonths === null) {
                    $fail('termMonths', __('How many months does the subscription run?'));
                } elseif ($upfront !== null && $termMonths > 12) {
                    $fail('upfrontPrice', __('A one-off price needs a term of at most twelve months.'));
                }
                if ($this->input('afterTerm') === 'monthly' && $monthly === null) {
                    $fail('monthlyPrice', __('Running on monthly after the term needs a monthly price.'));
                }
                break;

            case 'season':
                if ($this->input('seasonEnds') === null) {
                    $fail('seasonEnds', __('On which day does the season end?'));
                }
                break;
        }
    }
}
