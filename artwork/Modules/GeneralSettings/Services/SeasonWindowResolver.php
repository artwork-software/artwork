<?php

namespace Artwork\Modules\GeneralSettings\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Carbon\Carbon;

/**
 * Einzige Quelle für das Spielzeit-Fenster (GeneralSettings playing_time_window_start/_end,
 * Toolsettings > Kommunikation & Rechtliches).
 *
 * Produktentscheidung (PO, 09/2026): Ist keine (oder eine ungültige) Spielzeit hinterlegt, gilt das
 * laufende KALENDERJAHR als Spielzeit – statt Fehler ("nicht konfiguriert") oder Auswertung "aller
 * Zeiträume". Aufrufer, die einen Hinweis zeigen wollen, lesen `configured`.
 *
 * Genutzt von ShiftKpiTrackingService (Kennzahlen, Regelprüfungen, Info-Modal) und den BI-Services
 * (Dashboard/Export-Standardzeitraum) – die Regel lebt nur hier.
 */
class SeasonWindowResolver
{
    public function __construct(private readonly GeneralSettings $generalSettings)
    {
    }

    /**
     * Spielzeit-Fenster mit Fallback: konfiguriertes Fenster, sonst 01.01.–31.12. des laufenden Jahres
     * (App-Zeitzone). `configured` ist nur true, wenn ein gültiges Fenster hinterlegt ist.
     *
     * @return array{start: Carbon, end: Carbon, configured: bool}
     */
    public function resolve(): array
    {
        $configured = $this->configuredWindow();
        if ($configured !== null) {
            return ['start' => $configured[0], 'end' => $configured[1], 'configured' => true];
        }

        $now = Carbon::now();

        return [
            'start' => $now->copy()->startOfYear()->startOfDay(),
            'end' => $now->copy()->endOfYear()->endOfDay(),
            'configured' => false,
        ];
    }

    /**
     * Fenster als Tupel (immer gesetzt, siehe resolve()).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bounds(): array
    {
        $season = $this->resolve();

        return [$season['start'], $season['end']];
    }

    /**
     * true nur bei gültig hinterlegter Spielzeit (beide Daten gesetzt, parsebar, Ende nicht vor Beginn).
     */
    public function isConfigured(): bool
    {
        return $this->configuredWindow() !== null;
    }

    /**
     * Kalenderjahr, das als Fallback gilt (für Hinweistexte).
     */
    public function fallbackYear(): int
    {
        return Carbon::now()->year;
    }

    /**
     * Nur das konfigurierte Fenster – null, wenn leer oder ungültig.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function configuredWindow(): ?array
    {
        $startRaw = trim((string) ($this->generalSettings->playing_time_window_start ?? ''));
        $endRaw = trim((string) ($this->generalSettings->playing_time_window_end ?? ''));
        if ($startRaw === '' || $endRaw === '') {
            return null;
        }

        try {
            $start = Carbon::parse($startRaw)->startOfDay();
            $end = Carbon::parse($endRaw)->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lt($start)) {
            return null;
        }

        return [$start, $end];
    }
}
