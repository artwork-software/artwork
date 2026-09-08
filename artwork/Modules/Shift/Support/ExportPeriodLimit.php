<?php

namespace Artwork\Modules\Shift\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Zeitraum-Deckel für Dienstplan-Exporte und -Listen (Schichtverlauf, Regelverstöße,
 * Änderungsübersicht): höchstens ein Jahr (366 Tage), sonst 422. Schützt vor unbegrenzten
 * Excel-Läufen über den gesamten Datenbestand.
 *
 * Exporte: resolveBounds() füllt fehlende Grenzen deterministisch auf (beide fehlen → aktueller
 * Monat, nur eine Grenze → die andere im Abstand MAX_DAYS) und prüft danach den Deckel — so ist
 * ein Export nie unbegrenzt. Listen: clampForList() begrenzt statt zu werfen (Inertia-GET).
 */
final class ExportPeriodLimit
{
    public const MAX_DAYS = 366;

    /**
     * Wirft eine ValidationException (422) auf $field, wenn der Zeitraum länger als MAX_DAYS ist.
     * Fehlende Grenzen (null/leer) werden nicht geprüft — Exporte nutzen resolveBounds().
     */
    public static function assertWithinLimit(
        CarbonInterface|string|null $from,
        CarbonInterface|string|null $to,
        string $field = 'date_to'
    ): void {
        if (self::isEmpty($from) || self::isEmpty($to)) {
            return;
        }

        if (self::exceedsLimit(self::day($from), self::day($to))) {
            throw ValidationException::withMessages([
                $field => __('The period may cover at most one year.'),
            ]);
        }
    }

    /**
     * Zeitraum für Exporte: fehlende Grenzen deterministisch auffüllen, dann Deckel prüfen (422).
     * - beide fehlen → aktueller Monat (Monatsanfang bis Monatsende)
     * - nur "von" → "bis" = von + MAX_DAYS
     * - nur "bis" → "von" = bis − MAX_DAYS
     * Rückgabe: [von (Tagesanfang), bis (Tagesende)] in der App-Zeitzone.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolveBounds(
        CarbonInterface|string|null $from,
        CarbonInterface|string|null $to,
        string $field = 'date_to',
        ?string $timezone = null
    ): array {
        $timezone ??= config('app.timezone', 'Europe/Berlin');
        $hasFrom = !self::isEmpty($from);
        $hasTo = !self::isEmpty($to);

        if (!$hasFrom && !$hasTo) {
            $now = Carbon::now($timezone);
            $start = $now->copy()->startOfMonth()->startOfDay();
            $end = $now->copy()->endOfMonth()->startOfDay();
        } elseif ($hasFrom && $hasTo) {
            $start = self::day($from, $timezone);
            $end = self::day($to, $timezone);
        } elseif ($hasFrom) {
            $start = self::day($from, $timezone);
            $end = $start->copy()->addDays(self::MAX_DAYS);
        } else {
            $end = self::day($to, $timezone);
            $start = $end->copy()->subDays(self::MAX_DAYS);
        }

        self::assertWithinLimit($start, $end, $field);

        return [$start, $end->endOfDay()];
    }

    /**
     * Zeitraum für Listen (Inertia-GET, kein 422): ist "bis" weiter als MAX_DAYS nach "von", wird "bis"
     * auf von + MAX_DAYS begrenzt und period_clamped gesetzt. Einzelne oder fehlende Grenzen bleiben
     * unverändert (Liste ohne Zeitraum = alle Einträge des gewählten Status).
     *
     * @return array{date_from: string|null, date_to: string|null, period_clamped: bool}
     */
    public static function clampForList(?string $from, ?string $to): array
    {
        $from = self::isEmpty($from) ? null : $from;
        $to = self::isEmpty($to) ? null : $to;

        if ($from === null || $to === null || !self::exceedsLimit(self::day($from), self::day($to))) {
            return ['date_from' => $from, 'date_to' => $to, 'period_clamped' => false];
        }

        return [
            'date_from' => $from,
            'date_to' => self::day($from)->addDays(self::MAX_DAYS)->toDateString(),
            'period_clamped' => true,
        ];
    }

    private static function exceedsLimit(Carbon $start, Carbon $end): bool
    {
        return $start->diffInDays($end, false) > self::MAX_DAYS;
    }

    private static function isEmpty(CarbonInterface|string|null $value): bool
    {
        return $value === null || $value === '';
    }

    private static function day(CarbonInterface|string $value, ?string $timezone = null): Carbon
    {
        $day = $value instanceof CarbonInterface
            ? Carbon::instance($value)
            : Carbon::parse($value, $timezone);

        return $day->startOfDay();
    }
}
