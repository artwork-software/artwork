<?php

namespace Artwork\Modules\GeneralSettings\Models;

use Spatie\LaravelSettings\Settings;

/**
 * Regionale Formate der Instanz – eine Stelle für Zahlen, Währung und Datum in Oberfläche,
 * PDFs und Exporten (vorher fest de-DE/EUR/d.m.Y an vielen Stellen).
 */
class FormatSettings extends Settings
{
    /** Gängige Varianten; Trennzeichen kommen aus dem Locale (z. B. de-CH → 1'234.50) */
    public const NUMBER_LOCALES = ['de-DE', 'de-AT', 'de-CH', 'fr-CH', 'en-GB', 'en-US'];

    public const CURRENCIES = ['EUR', 'CHF', 'GBP', 'USD'];

    public const DATE_FORMATS = ['d.m.Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];

    public string $number_locale = 'de-DE';

    public string $currency = 'EUR';

    public string $date_format = 'd.m.Y';

    public static function group(): string
    {
        return 'format';
    }
}
