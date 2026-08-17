<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Deterministischer Zufallsgenerator für die Demo-Seeder.
 *
 * Jeder Kontext (z.B. "2026-09|projects") bekommt seine eigene, aus dem
 * Seed-Key abgeleitete Sequenz. Dadurch ist jeder Seeder-Lauf reproduzierbar:
 * Ein zweiter Lauf über denselben Monat trifft dieselben Entscheidungen und
 * erzeugt (zusammen mit firstOrCreate) keine Duplikate.
 */
final class DemoRandom
{
    private int $state;

    public function __construct(string $seedKey)
    {
        $seed = crc32('artwork-demo|' . $seedKey);
        $this->state = $seed !== 0 ? $seed & 0x7FFFFFFF : 0x1234567;
    }

    /** Abgeleiteter Generator für einen Unterkontext (stabil, unabhängig von der Aufrufreihenfolge). */
    public function fork(string $key): self
    {
        return new self($this->state . '|' . $key);
    }

    private function next(): int
    {
        // xorshift32 — schnell, reproduzierbar, plattformunabhängig
        $x = $this->state;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= ($x >> 17);
        $x ^= ($x << 5) & 0xFFFFFFFF;

        return $this->state = $x & 0x7FFFFFFF;
    }

    public function int(int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return $min + ($this->next() % ($max - $min + 1));
    }

    /** true mit der gegebenen Wahrscheinlichkeit (0.0–1.0). */
    public function chance(float $probability): bool
    {
        return $this->int(0, 9999) < (int) round($probability * 10000);
    }

    /**
     * @template T
     * @param array<T> $items
     * @return T
     */
    public function pick(array $items)
    {
        $items = array_values($items);
        if ($items === []) {
            throw new \InvalidArgumentException('DemoRandom::pick() mit leerem Array aufgerufen.');
        }

        return $items[$this->int(0, count($items) - 1)];
    }

    /**
     * Zieht $count verschiedene Elemente (oder weniger, wenn das Array kleiner ist).
     *
     * @template T
     * @param array<T> $items
     * @return array<T>
     */
    public function pickMany(array $items, int $count): array
    {
        $items = $this->shuffle($items);

        return array_slice($items, 0, min($count, count($items)));
    }

    /**
     * Gewichtete Auswahl: ['schlüssel' => gewicht, ...] — liefert den Schlüssel.
     *
     * @param array<int|string, int> $weights
     */
    public function weighted(array $weights): int|string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            throw new \InvalidArgumentException('DemoRandom::weighted() ohne positive Gewichte aufgerufen.');
        }
        $roll = $this->int(1, $total);
        foreach ($weights as $key => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }

    /**
     * Fisher-Yates mit eigenem RNG (array_shuffle wäre nicht deterministisch).
     *
     * @template T
     * @param array<T> $items
     * @return array<T>
     */
    public function shuffle(array $items): array
    {
        $items = array_values($items);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = $this->int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }
}
