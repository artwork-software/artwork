<?php

namespace Artwork\Core\Localization;

/**
 * Sprachen der Oberfläche, Mails und Exporte aus `app.supported_locales`.
 *
 * Eine neue Sprache braucht nur einen Eintrag dort (Code => Eigenname) sowie `lang/<code>.json`
 * und `lang/<code>/*.php`. Sprachauswahl, Middleware und Empfängersprache lesen alle von hier.
 */
final class SupportedLocales
{
    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::names());
    }

    /**
     * Code => Eigenname der Sprache (z. B. "nl" => "Nederlands"), damit jede Sprache sich in der
     * Auswahl selbst benennt, unabhängig von der gerade aktiven Oberflächensprache.
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        $configured = (array) config('app.supported_locales', []);

        // Älteres Format ['de', 'en'] weiter verstehen: der Code dient dann als Name
        if (array_is_list($configured)) {
            $configured = array_combine($configured, $configured);
        }

        $names = [];
        foreach ($configured as $code => $name) {
            if (is_string($code) && $code !== '') {
                $names[$code] = is_string($name) && $name !== '' ? $name : $code;
            }
        }

        if ($names === []) {
            $fallback = (string) config('app.fallback_locale', 'en');
            $names[$fallback] = $fallback;
        }

        return $names;
    }

    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::codes(), true);
    }

    /**
     * Für die Auswahllisten im Frontend.
     *
     * @return list<array{code: string, name: string}>
     */
    public static function toFrontend(): array
    {
        $options = [];
        foreach (self::names() as $code => $name) {
            $options[] = ['code' => $code, 'name' => $name];
        }

        return $options;
    }
}
