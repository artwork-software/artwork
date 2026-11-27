<?php

namespace Artwork\Modules\Shift\Enums;

/**
 * Grund einer kurzfristigen Änderung an einem verbindlichen Dienst (Änderung nach dem Stichzeitpunkt
 * am Vortag). Die Werte sind zugleich die Übersetzungsschlüssel der Oberfläche.
 */
enum ShortNoticeReason: string
{
    case Cancellation = 'cancellation';
    case OperationalDisruption = 'operational_disruption';
    case OperationalNecessity = 'operational_necessity';
    case MutualConsent = 'mutual_consent';
    case Other = 'other';

    /**
     * Englischer Übersetzungsschlüssel (lang/*.json) der Bezeichnung.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cancellation => 'Cancellation',
            self::OperationalDisruption => 'Operational disruption',
            self::OperationalNecessity => 'Operational necessity',
            self::MutualConsent => 'By mutual consent with the person',
            self::Other => 'Other reason',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $reason): array => ['value' => $reason->value, 'label' => $reason->label()],
            self::cases()
        );
    }
}
