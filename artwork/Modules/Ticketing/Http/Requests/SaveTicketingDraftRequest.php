<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

/**
 * Wie die angegebenen Termine verkaufen: Plätze gesamt (null = jede Klasse verkauft genau ihre Plätze),
 * Preisklassen (Plätze leer = bis alle Plätze verkauft sind), ein eigener Text für den Shop (null = der
 * der Produktion) und die Ermäßigungen, in denen sie von der Produktion abweichen.
 */
class SaveTicketingDraftRequest extends TicketingEventsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'capacity' => 'present|nullable|integer|min:0|max:1000000',
            'classes' => 'required|array|min:1|max:50',
            'classes.*.zone_key' => 'present|nullable|string|max:32',
            'classes.*.name' => 'required|string|max:60',
            'classes.*.price_cents' => 'required|integer|min:0',
            'classes.*.quota' => $this->input('capacity') === null
                ? 'required|integer|min:0|max:1000000'
                : 'present|nullable|integer|min:0|lte:capacity',
            'description' => 'present|nullable|string|max:4000',
            'reductions' => 'present|array|max:50',
            'reductions.*.id' => 'required|uuid|distinct',
            'reductions.*.offered' => 'required|boolean',
            ...self::eventIdsRule(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'classes.*.quota.lte' => __('A price class cannot have more places than the date in total.'),
        ];
    }

    /**
     * @return array{
     *     capacity: int|null,
     *     classes: list<array{zone_key: string|null, name: string, price_cents: int, quota: int|null}>,
     *     description: string|null,
     *     reductions: list<array{id: string, offered: bool}>,
     * }
     */
    public function draft(): array
    {
        return [
            'capacity' => $this->validated('capacity') === null ? null : (int) $this->validated('capacity'),
            'classes' => array_map(static fn (array $class): array => [
                'zone_key' => $class['zone_key'],
                'name' => $class['name'],
                'price_cents' => (int) $class['price_cents'],
                'quota' => $class['quota'] === null ? null : (int) $class['quota'],
            ], $this->validated('classes')),
            'description' => $this->validated('description'),
            'reductions' => array_map(static fn (array $reduction): array => [
                'id' => $reduction['id'],
                'offered' => (bool) $reduction['offered'],
            ], $this->validated('reductions')),
        ];
    }
}
