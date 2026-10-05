<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/**
 * Zwei Schutzmechanismen für Seiten-Payloads:
 *
 * 1. Struktur-Snapshot: Der Schlüsselbaum der Inertia-Props (nur Keys, Listen auf ihr
 *    erstes Element reduziert, keine Werte) wird mit einer gespeicherten JSON-Datei
 *    verglichen. Fällt ein Feld weg oder kommt eines dazu, schlägt der Test fehl — wer
 *    die Struktur bewusst ändert, aktualisiert den Snapshot mit UPDATE_PROP_SNAPSHOTS=1
 *    und prüft dabei die Konsumenten im Frontend (Optional Chaining lässt fehlende
 *    Felder sonst still verschwinden).
 *
 * 2. Wiederholte Query-Muster: Während des Requests werden die SQL-Statements
 *    normalisiert gezählt. Taucht dasselbe Muster öfter als erlaubt auf, ist das ein
 *    N+1 — unabhängig von der absoluten Query-Zahl der Seite.
 */
trait AssertsPropShape
{
    /** @var array<int, string> Query-Muster, die legitim mehrfach laufen (Settings-Gruppen, Session) */
    private array $repeatedQueryAllowlist = [
        'from `settings`',
        'from `sessions`',
        'into `sessions`',
        'update `sessions`',
    ];

    private function snapshotPath(string $name): string
    {
        return base_path('tests/Feature/PropShape/__snapshots__/' . $name . '.json');
    }

    /**
     * Schlüsselbaum eines Props-Werts: assoziative Arrays → Keys mit Kind-Struktur,
     * Listen (und Maps mit Datums-/ID-Keys) → Struktur des ersten Elements unter "[]",
     * Blätter → ".".
     */
    protected function propShape(mixed $value): mixed
    {
        if (!is_array($value)) {
            return '.';
        }
        if ($value === []) {
            return '[]';
        }
        if (array_is_list($value) || $this->isMapWithDynamicKeys($value)) {
            // Vereinigung über alle Elemente: das erste Element allein wäre bei leeren
            // Tagen/Listen oder optionalen Feldern (project: null) nicht aussagekräftig
            $merged = '[]';
            foreach ($value as $element) {
                $merged = $this->mergeShapes($merged, $this->propShape($element));
            }

            return ['[]' => $merged];
        }

        ksort($value);

        return array_map(fn (mixed $child) => $this->propShape($child), $value);
    }

    private function mergeShapes(mixed $a, mixed $b): mixed
    {
        if (is_array($a) && is_array($b)) {
            foreach ($b as $key => $child) {
                $a[$key] = array_key_exists($key, $a) ? $this->mergeShapes($a[$key], $child) : $child;
            }
            ksort($a);

            return $a;
        }
        if (is_array($a)) {
            return $a;
        }
        if (is_array($b)) {
            return $b;
        }

        return $a === '[]' ? $b : $a;
    }

    /**
     * Maps mit Datums- oder numerischen Keys (daysWithData, byId-Lookups) sind Listen,
     * keine Strukturen — ihre Keys dürfen nicht in den Snapshot.
     *
     * @param array<array-key, mixed> $value
     */
    private function isMapWithDynamicKeys(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            $key = (string) $key;
            if (!ctype_digit($key) && !preg_match('/^\d{4}-\d{2}-\d{2}/', $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $only Top-Level-Props, die in den Snapshot gehören
     */
    protected function assertPropShapeMatchesSnapshot(TestResponse $response, string $name, array $only): void
    {
        $props = $response->viewData('page')['props'] ?? null;
        $this->assertIsArray($props, "Inertia-Props fehlen in der Antwort ($name)");

        foreach ($only as $prop) {
            $this->assertArrayHasKey($prop, $props, "Prop '$prop' fehlt in der Antwort ($name)");
        }

        $shape = $this->propShape(array_intersect_key($props, array_flip($only)));
        $actual = $this->flattenShape($shape);
        $path = $this->snapshotPath($name);

        if (!file_exists($path) || getenv('UPDATE_PROP_SNAPSHOTS')) {
            file_put_contents($path, json_encode($shape, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            $this->assertTrue(true);
            fwrite(STDERR, "\n[PropShape] Snapshot geschrieben: $path\n");

            return;
        }

        $expected = $this->flattenShape(json_decode((string) file_get_contents($path), true));
        $missing = array_values(array_diff($expected, $actual));
        $added = array_values(array_diff($actual, $expected));

        $this->assertSame(
            [],
            $missing,
            "Felder aus dem Snapshot '$name' fehlen im Payload — Konsumenten im Frontend prüfen, "
            . "dann Snapshot mit UPDATE_PROP_SNAPSHOTS=1 aktualisieren:\n  " . implode("\n  ", $missing)
        );
        $this->assertSame(
            [],
            $added,
            "Neue Felder im Payload, die der Snapshot '$name' nicht kennt — "
            . "Snapshot mit UPDATE_PROP_SNAPSHOTS=1 aktualisieren:\n  " . implode("\n  ", $added)
        );
    }

    /**
     * @return array<int, string> Pfade wie "daysWithData.[].shifts.[].craft.id"
     */
    private function flattenShape(mixed $shape, string $prefix = ''): array
    {
        if (!is_array($shape)) {
            return [$prefix === '' ? (string) $shape : $prefix];
        }

        $paths = [];
        foreach ($shape as $key => $child) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            foreach ($this->flattenShape($child, $path) as $childPath) {
                $paths[] = $childPath;
            }
        }

        return $paths;
    }

    /**
     * Führt den Request aus und schlägt fehl, wenn ein normalisiertes SQL-Muster öfter
     * als $maxRepeats vorkommt (N+1). Die Testdaten müssen mehr als $maxRepeats Zeilen
     * je Entität enthalten, sonst bleibt ein N+1 unter der Schwelle.
     *
     * @param callable(): TestResponse $request
     */
    /**
     * @param array<int, string> $allow zusätzliche Muster-Ausschnitte, die auf dieser Seite
     *                                  bekannt mehrfach laufen (Altlast, nicht Gegenstand des Tests)
     */
    protected function assertNoRepeatedQueryPatterns(
        callable $request,
        int $maxRepeats = 4,
        array $allow = []
    ): TestResponse {
        $counts = [];
        $collect = true;
        DB::listen(function ($query) use (&$counts, &$collect): void {
            if (!$collect) {
                return;
            }
            $sql = preg_replace('/\d+/', 'N', $query->sql);
            $sql = preg_replace('/in \((\?, )*\?\)/', 'in (…)', (string) $sql);
            $counts[$sql] = ($counts[$sql] ?? 0) + 1;
        });

        try {
            $response = $request();
        } finally {
            $collect = false;
        }

        $violations = [];
        foreach ($counts as $sql => $count) {
            if ($count <= $maxRepeats) {
                continue;
            }
            foreach ([...$this->repeatedQueryAllowlist, ...$allow] as $allowed) {
                if (str_contains($sql, $allowed)) {
                    continue 2;
                }
            }
            $violations[] = sprintf('%dx %s', $count, mb_substr($sql, 0, 160));
        }

        $this->assertSame(
            [],
            $violations,
            "Wiederholte Query-Muster (N+1) im Request:\n  " . implode("\n  ", $violations)
        );

        return $response;
    }
}
