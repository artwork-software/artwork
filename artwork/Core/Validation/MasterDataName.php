<?php

namespace Artwork\Core\Validation;

use Illuminate\Http\Request;

/**
 * Namensregeln für einfache Stammdaten (Genres, Kategorien, Bereiche, Projektstatus, Vertragsarten,
 * Unternehmensformen, Währungen): Pflicht und höchstens 255 Zeichen. Gleiche Namen sind erlaubt,
 * weil Dubletten gewollt sein können (Entscheidung 20.11.2026).
 */
final class MasterDataName
{
    /**
     * @param bool $updating beim Bearbeiten darf der Name fehlen (nur Farbe geändert), aber nicht leer sein
     * @return array<int, string>
     */
    public static function rules(bool $updating = false): array
    {
        return [$updating ? 'sometimes' : 'required', 'filled', 'string', 'max:255'];
    }

    /**
     * Prüft den Namen mit lesbaren Meldungen ("Bitte gib einen Namen ein." statt "Das name-Feld …").
     */
    public static function validate(Request $request, bool $updating = false): void
    {
        $request->validate(['name' => self::rules($updating)], [
            'name.required' => __('Please enter a name.'),
            'name.filled' => __('Please enter a name.'),
            'name.max' => __('The name may have at most 255 characters.'),
        ]);
    }
}
