<?php

namespace Artwork\Modules\AiAssistant\Support;

/**
 * Die Referenzfaelle des `args_hash` (contracts/action-contract.md).
 *
 * Der Contract verlangt identische Fixtures in beiden Repos; erzeugt werden sie aus **dieser**
 * Implementierung (`ai:export-action-fixtures`), damit die Hashes nie von Hand gepflegt werden.
 * Die vier Namen stehen im Contract und sind bindend.
 */
final class AiActionHashFixtures
{
    /** Verzeichnis der Fixtures, relativ zum Repo bzw. zum Spec-Workspace. */
    public const DIRECTORY = 'contracts/fixtures/args-hash';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function cases(): array
    {
        return [
            // Serienanlage mit allen Feldern des Serienbeschriebs.
            'create-events-series' => [
                'project_id' => 42,
                'series_spec' => [
                    'from' => '2026-09-14',
                    'to' => '2026-09-28',
                    'weekdays' => [1, 3],
                    'start_time' => '19:00',
                    'duration_minutes' => 120,
                    'room_id' => 7,
                    'type_id' => 3,
                    'name' => 'Vorstellung',
                ],
            ],
            // Aenderung eines Termins: event_id kommt aus dem Pfad und gehoert in den Hash.
            'update-event-times' => [
                'event_id' => 1234,
                'start_time' => '20:00',
                'end_time' => '22:00',
            ],
            // Liste: die Reihenfolge ist Teil der Bedeutung und wird nicht sortiert.
            'delete-events-batch' => [
                'ids' => [11, 12, 13],
            ],
            // Schraegstriche und Umlaute bleiben unescaped — sonst rechnen beide Seiten anders.
            'unicode-and-slashes' => [
                'name' => 'Haus/Bühne',
                'note' => 'Café — 20:00',
            ],
        ];
    }

    /**
     * @return array<string, array{args: array<string, mixed>, hash: string}>
     */
    public static function withHashes(): array
    {
        $fixtures = [];

        foreach (self::cases() as $name => $args) {
            $fixtures[$name] = ['args' => $args, 'hash' => AiActionHash::of($args)];
        }

        return $fixtures;
    }
}
