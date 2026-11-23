<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Findet bei der Anlage bestehende Projekte und Projektgruppen mit gleichem oder ähnlichem Namen
 * (Kundenwunsch: Hinweis auf mögliche Dubletten, Anlegen bleibt trotzdem möglich).
 *
 * Verglichen wird auf normalisierten Namen (Kleinschreibung, Umlaute ausgeschrieben, Satzzeichen
 * entfernt). Treffer entstehen durch Gleichheit, Enthaltensein, wenige Tippfehler (Levenshtein)
 * oder gemeinsame markante Wörter. Projekte im Papierkorb bleiben außen vor.
 */
class ProjectNameSimilarityService
{
    public const MATCH_IDENTICAL = 'identical';

    public const MATCH_SIMILAR = 'similar';

    /** Kürzere Namen erzeugen beim Enthaltensein zu viele Zufallstreffer („Tanz“ in „Tanzfestival“ ist gewollt, „Die“ nicht). */
    private const MIN_CONTAINS_LENGTH = 4;

    /** Nur längere Wörter zählen als markant, damit „Tanz im August“ nicht zu „Tanz der Vampire“ passt. */
    private const MIN_SHARED_WORD_LENGTH = 5;

    private const STOP_WORDS = [
        'der', 'die', 'das', 'den', 'dem', 'des', 'und', 'oder', 'ein', 'eine', 'einer', 'eines', 'im', 'in',
        'am', 'auf', 'mit', 'von', 'vom', 'zum', 'zur', 'fuer', 'the', 'and', 'of', 'for', 'a', 'an',
    ];

    /**
     * @return Collection<int, array{project: Project, match: string, score: int}>
     */
    public function findSimilar(string $name, int $limit = 10): Collection
    {
        $normalizedName = $this->normalize($name);
        if ($this->compact($normalizedName) === '') {
            return collect();
        }

        // Erst nur Namen ohne Model-Hydrierung durchsehen, dann die wenigen Treffer als Models laden
        $scores = [];
        $names = [];
        foreach (Project::query()->toBase()->get(['id', 'name']) as $row) {
            $score = $this->score($normalizedName, $this->normalize((string) $row->name));
            if ($score !== null) {
                $scores[(int) $row->id] = $score;
                $names[(int) $row->id] = (string) $row->name;
            }
        }

        uksort($scores, static fn (int $a, int $b): int => [$scores[$b], $names[$a]] <=> [$scores[$a], $names[$b]]);
        $scores = array_slice($scores, 0, $limit, true);
        if ($scores === []) {
            return collect();
        }

        $projects = Project::query()->whereKey(array_keys($scores))->get()->keyBy('id');

        return collect($scores)
            ->filter(static fn (int $score, int $projectId): bool => $projects->has($projectId))
            ->map(static fn (int $score, int $projectId): array => [
                'project' => $projects->get($projectId),
                'match' => $score === 100 ? self::MATCH_IDENTICAL : self::MATCH_SIMILAR,
                'score' => $score,
            ])
            ->values();
    }

    /**
     * Ähnlichkeitswert 0–100 (100 = gleicher Name), null = kein Treffer.
     */
    public function score(string $normalizedName, string $normalizedCandidate): ?int
    {
        $name = $this->compact($normalizedName);
        $candidate = $this->compact($normalizedCandidate);
        if ($name === '' || $candidate === '') {
            return null;
        }

        if ($name === $candidate) {
            return 100;
        }

        $nameLength = strlen($name);
        $candidateLength = strlen($candidate);
        $shorterLength = min($nameLength, $candidateLength);

        if (
            $shorterLength >= self::MIN_CONTAINS_LENGTH
            && (str_contains($candidate, $name) || str_contains($name, $candidate))
        ) {
            return 80;
        }

        $allowedDistance = $this->allowedTypos($shorterLength);
        if ($allowedDistance > 0 && abs($nameLength - $candidateLength) <= $allowedDistance) {
            $distance = levenshtein($name, $candidate);
            if ($distance <= $allowedDistance) {
                return 75 - $distance;
            }
        }

        $sharedWordRatio = $this->sharedWordRatio($normalizedName, $normalizedCandidate);
        if ($sharedWordRatio >= 0.5) {
            return 50 + (int) round($sharedWordRatio * 20);
        }

        return null;
    }

    public function normalize(string $name): string
    {
        $ascii = Str::lower(Str::ascii($name, 'de'));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $ascii));
    }

    private function compact(string $normalizedName): string
    {
        return str_replace(' ', '', $normalizedName);
    }

    private function allowedTypos(int $length): int
    {
        return match (true) {
            $length < 5 => 0,
            $length < 9 => 1,
            $length < 14 => 2,
            default => 3,
        };
    }

    private function sharedWordRatio(string $normalizedName, string $normalizedCandidate): float
    {
        $nameWords = $this->significantWords($normalizedName);
        $candidateWords = $this->significantWords($normalizedCandidate);
        if ($nameWords === [] || $candidateWords === []) {
            return 0.0;
        }

        $sharedWords = array_filter(
            array_intersect($nameWords, $candidateWords),
            static fn (string $word): bool => strlen($word) >= self::MIN_SHARED_WORD_LENGTH
        );

        return count($sharedWords) / max(count($nameWords), count($candidateWords));
    }

    /**
     * @return array<int, string>
     */
    private function significantWords(string $normalizedName): array
    {
        return array_values(array_unique(array_filter(
            explode(' ', $normalizedName),
            static fn (string $word): bool => strlen($word) >= 3
                && !ctype_digit($word)
                && !in_array($word, self::STOP_WORDS, true)
        )));
    }
}
