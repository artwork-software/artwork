<?php

namespace Artwork\Modules\GeneralSettings\Services;

use Artwork\Modules\GeneralSettings\Models\FormatSettings;
use Carbon\Carbon;
use DateTimeInterface;
use NumberFormatter;

/**
 * Zahlen, Beträge und Daten im Format der Instanz (Einstellungen → Tool → Regionale Formate).
 * Frontend-Gegenstück: resources/js/Helper/instanceFormat.js (gleiche Einstellungen per Prop).
 */
readonly class InstanceFormatter
{
    /** Rückfall ohne intl-Erweiterung: [Tausender, Dezimal] je Locale */
    private const SEPARATORS = [
        'de-DE' => ['.', ','],
        'de-AT' => [' ', ','],
        'de-CH' => ["'", '.'],
        'fr-CH' => [' ', ','],
        'en-GB' => [',', '.'],
        'en-US' => [',', '.'],
    ];

    private const CURRENCY_SYMBOLS = ['EUR' => '€', 'CHF' => 'CHF', 'GBP' => '£', 'USD' => '$'];

    public function __construct(private FormatSettings $settings)
    {
    }

    public function number(float|int|string|null $value, int $decimals = 2): string
    {
        $number = $this->toFloat($value);

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($this->settings->number_locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

            return (string) $formatter->format($number);
        }

        [$thousands, $decimal] = self::SEPARATORS[$this->settings->number_locale] ?? self::SEPARATORS['de-DE'];

        return number_format($number, $decimals, $decimal, $thousands);
    }

    public function currency(float|int|string|null $value): string
    {
        $number = $this->toFloat($value);

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($this->settings->number_locale, NumberFormatter::CURRENCY);

            return (string) $formatter->formatCurrency($number, $this->settings->currency);
        }

        return $this->number($number) . ' ' . $this->currencySymbol();
    }

    /**
     * Excel-Zahlenformat für Beträge: Trennzeichen zeigt Excel nach der Locale der Lesenden an,
     * nur das Währungssymbol kommt aus der Instanz.
     */
    public function excelCurrencyFormat(): string
    {
        return '#,##0.00 "' . $this->currencySymbol() . '"';
    }

    public function currencySymbol(): string
    {
        return self::CURRENCY_SYMBOLS[$this->settings->currency] ?? $this->settings->currency;
    }

    public function date(DateTimeInterface|string|null $value): string
    {
        return $value === null || $value === '' ? '' : Carbon::parse($value)->format($this->settings->date_format);
    }

    public function dateTime(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return Carbon::parse($value)->format($this->settings->date_format . ' H:i');
    }

    /**
     * @return array{numberLocale: string, currency: string, dateFormat: string}
     */
    public function toFrontend(): array
    {
        return [
            'numberLocale' => $this->settings->number_locale,
            'currency' => $this->settings->currency,
            'dateFormat' => $this->settings->date_format,
        ];
    }

    private function toFloat(float|int|string|null $value): float
    {
        if (is_string($value) && !is_numeric($value)) {
            // deutsche Eingaben wie "1.234,5" tolerieren
            $value = str_replace(['.', ','], ['', '.'], $value);
        }

        return (float) $value;
    }
}
