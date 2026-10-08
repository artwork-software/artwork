<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

/**
 * Wie die angegebenen Termine verkaufen: Preisklassen, ein eigener Text für den Shop (null = der
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
            'classes' => 'required|array|min:1|max:50',
            'classes.*.zone_key' => 'present|nullable|string|max:32',
            'classes.*.name' => 'required|string|max:60',
            'classes.*.price_cents' => 'required|integer|min:0',
            'classes.*.quota' => 'required|integer|min:0|max:1000000',
            'description' => 'present|nullable|string|max:4000',
            'reductions' => 'present|array|max:50',
            'reductions.*.id' => 'required|uuid|distinct',
            'reductions.*.offered' => 'required|boolean',
            ...self::eventIdsRule(),
        ];
    }

    /**
     * @return array{
     *     classes: list<array{zone_key: string|null, name: string, price_cents: int, quota: int}>,
     *     description: string|null,
     *     reductions: list<array{id: string, offered: bool}>,
     * }
     */
    public function draft(): array
    {
        return [
            'classes' => $this->validated('classes'),
            'description' => $this->validated('description'),
            'reductions' => array_map(static fn (array $reduction): array => [
                'id' => $reduction['id'],
                'offered' => (bool) $reduction['offered'],
            ], $this->validated('reductions')),
        ];
    }
}
