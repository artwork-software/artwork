<?php

namespace Artwork\Core\Api\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Grundlage für lesende Anfragen der Maschinen-API.
 *
 * Fehlerhafte Parameter beantwortet die Schnittstelle mit 400 statt Laravels 422: Externe Systeme
 * unterscheiden üblicherweise nur „Anfrage falsch“ (400) und „Zugriff verweigert“ (401/403).
 * Der Rumpf bleibt Laravels Format mit message und errors.
 */
abstract class MachineApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Berechtigung prüft der Scope des Tokens (CheckToken) in der Route.
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 400));
    }

    /**
     * Wandelt eine kommagetrennte Liste (rooms=1,2,3) in ein Array um. Arrays (rooms[]=1) bleiben
     * unverändert, damit beide Schreibweisen funktionieren.
     *
     * @param list<string> $keys
     */
    protected function explodeCommaSeparated(array $keys): void
    {
        $normalized = [];

        foreach ($keys as $key) {
            $value = $this->query($key);

            if (is_string($value)) {
                $normalized[$key] = array_values(array_filter(
                    array_map('trim', explode(',', $value)),
                    static fn (string $item): bool => $item !== ''
                ));
            }
        }

        $this->merge($normalized);
    }
}
