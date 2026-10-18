<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * @property bool $use_first_name_for_sort
 * @property bool $calendar_abo_show_all_shifts
 * @property bool $allow_shift_overbooking
 * @property bool $granular_permissions_enabled
 * @property bool $hide_uncommitted_shifts_from_own_roster
 * @property bool $shift_confirmation_enabled
 * @property bool $shift_confirmation_in_history
 * @property bool $project_assignments_enabled
 * @property bool $work_time_accounting_enabled
 */
class ShiftSettings extends Settings
{
    public bool $use_first_name_for_sort;

    public bool $calendar_abo_show_all_shifts;

    public bool $allow_shift_overbooking;

    public bool $granular_permissions_enabled;

    // Einmal-Flag: granulare Default-Rechte wurden bereits an die Inhaber der
    // Master-Permission verteilt — verhindert, dass jedes erneute Aktivieren
    // individuell entzogene Rechte wieder zurückbringt.
    public bool $granular_defaults_granted;

    public bool $hide_uncommitted_shifts_from_own_roster;

    // Mitarbeitende können festgeschriebene Schichtzuweisungen selbst
    // zu-/absagen (bzw. Planer als Proxy für Externe).
    public bool $shift_confirmation_enabled;

    // Zu-/Absagen zusätzlich im Schichtverlauf anzeigen (geloggt wird immer).
    public bool $shift_confirmation_in_history;

    // Globaler Schalter für Projektzuordnungen/Wünsche im Dienstplan. Aus =
    // sämtliche Buttons/Anzeigen ausgeblendet, keine neuen Zuordnungen;
    // bestehende Daten bleiben erhalten und sind beim Wiedereinschalten zurück.
    public bool $project_assignments_enabled;

    // Globaler Schalter für die Arbeitszeitberechnung (Soll aus Arbeitszeitmustern,
    // Stundenkonto, Überstunden, nächtliche Buchung). Aus = der Dienstplan dient nur
    // zum Anlegen und Besetzen von Schichten; geplante Stunden bleiben sichtbar,
    // gespeicherte Konten, Muster und Verträge bleiben erhalten.
    // Default: Spatie füllt fehlende Werte damit auf – läuft der Code vor der
    // Settings-Migration, gibt es kein MissingSettings (500)
    public bool $work_time_accounting_enabled = true;

    public static function group(): string
    {
        return 'shift-settings';
    }
}
