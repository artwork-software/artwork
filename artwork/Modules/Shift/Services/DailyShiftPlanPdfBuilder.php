<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailyShiftPlanPdfBuilder
{
    private int $gapProminentFromMinutes = 45;

    /** Zielhöhe Grid (px) – Dompdf-friendly */
    private int $gridTargetHeightPx = 520;

    private int $gapRowHeightPx = 14;
    private int $activeRowMinHeightPx = 18;

    /** Pro Seite IMMER ein Tag */
    private int $maxChunksPerPage = 1;

    /** @var array<int,string> */
    private array $qualificationNameById = [];

    public function buildForProject(Project $project, User $user, bool $privacyMode = false): array
    {
        Carbon::setLocale('de');

        $this->qualificationNameById = $this->buildQualificationLookup($project);

        $legendCrafts = $this->buildCraftLegend($project);

        $dayChunks = $this->buildDayChunks($project);
        $pages = $this->packChunksIntoPages($dayChunks);

        return [
            'project' => $project,
            'pages'   => $pages,
            'created_by' => [
                'name' => $user->full_name,
                'email' => $user->email,
            ],
            'privacyMode' => $privacyMode,
            'meta'    => [
                'exportedAt'    => now(),
                'legendCrafts'  => $legendCrafts,
            ],
        ];
    }

    /** @return array<int,array{qid:int,name:string,sum:int,needed:int}> */
    private function buildShiftQualificationSummary($shift): array
    {
        $neededByQid = $this->neededCountByQualificationId($shift); // qid => needed
        $sumByQid = []; // qid => assignedSum

        $acc = function ($rel) use (&$sumByQid): void {
            foreach (($rel ?? []) as $p) {
                $qid = (int)($p->pivot?->shift_qualification_id ?? 0);
                if ($qid <= 0) continue;

                $c = (int)($p->pivot?->shift_count ?? 1);
                if ($c <= 0) $c = 1;

                $sumByQid[$qid] = ($sumByQid[$qid] ?? 0) + $c;
            }
        };

        $acc($shift->users);
        $acc($shift->freelancer);
        $acc($shift->serviceProvider);

        $lines = [];

        foreach (array_keys($neededByQid) as $qid) {
            $lines[] = [
                'qid'    => (int)$qid,
                'name'   => $this->qualificationNameById[(int)$qid] ?? ('Quali #' . (int)$qid),
                'sum'    => (int)($sumByQid[(int)$qid] ?? 0),
                'needed' => (int)($neededByQid[(int)$qid] ?? 0),
            ];
        }

        foreach ($sumByQid as $qid => $sum) {
            if (isset($neededByQid[$qid])) continue;

            $lines[] = [
                'qid'    => (int)$qid,
                'name'   => $this->qualificationNameById[(int)$qid] ?? ('Quali #' . (int)$qid),
                'sum'    => (int)$sum,
                'needed' => 0,
            ];
        }

        return $lines;
    }

    /** @return array<int,array{name:string,abbr:string,color:string,bg:string,quals:array<int,string>,pos:int}> */
    private function buildCraftLegend(Project $project): array
    {
        $crafts = $project->shifts
            ->map(fn ($s) => $s->craft)
            ->filter()
            ->unique('id')
            ->values();

        $crafts->loadMissing('qualifications:id,name');

        $out = [];
        foreach ($crafts as $craft) {
            $color = $this->normalizeHexColor($craft->color) ?? '#0ea5e9';

            $out[] = [
                'name'  => (string)($craft->name ?? ''),
                'abbr'  => (string)($craft->abbreviation ?? ''),
                'color' => $color,
                'bg'    => $this->mixWithWhite($color, 0.92),
                'quals' => $craft->qualifications?->pluck('name')->values()->all() ?? [],
                'pos'   => (int)($craft->position ?? 9999),
            ];
        }

        usort($out, fn($a, $b) => ($a['pos'] <=> $b['pos']) ?: strcmp($a['abbr'], $b['abbr']));
        return $out;
    }

    /** @return array<int,string> */
    private function buildQualificationLookup(Project $project): array
    {
        $ids = [];

        foreach ($project->shifts as $s) {
            foreach (($s->users ?? []) as $u) {
                if (!empty($u->pivot?->shift_qualification_id)) $ids[] = (int)$u->pivot->shift_qualification_id;
            }
            foreach (($s->freelancer ?? []) as $f) {
                if (!empty($f->pivot?->shift_qualification_id)) $ids[] = (int)$f->pivot->shift_qualification_id;
            }
            foreach (($s->serviceProvider ?? []) as $sp) {
                if (!empty($sp->pivot?->shift_qualification_id)) $ids[] = (int)$sp->pivot->shift_qualification_id;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) return [];

        return ShiftQualification::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->toArray();
    }

    private function buildDayChunks(Project $project): Collection
    {
        $allDates = collect();

        foreach ($project->shifts as $shift) {
            $allDates->push($this->shiftDate($shift));
        }
        foreach ($project->events as $event) {
            $allDates->push(Carbon::parse($event->earliest_start_datetime)->startOfDay());
        }

        $start = $allDates->min()?->copy() ?? now()->startOfDay();
        $end   = $allDates->max()?->copy() ?? now()->startOfDay();

        $chunks = collect();

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $chunks = $chunks->merge($this->buildChunksForSingleDay($project, $d->copy()));
        }

        return $chunks;
    }

    private function buildChunksForSingleDay(Project $project, Carbon $day): Collection
    {
        $dayKey = $day->toDateString();

        $events = $project->events
            ->filter(fn ($e) => Carbon::parse($e->earliest_start_datetime)->toDateString() === $dayKey)
            ->values();

        $shifts = $project->shifts
            ->filter(fn ($s) => $this->shiftDate($s)->toDateString() === $dayKey)
            ->values();

        $eventCards = $this->buildEventCardsCompact($events);
        $timelineBlocks = $this->buildTimelineBlocks($events);

        $timelineLanes = 1;
        foreach ($timelineBlocks as $b) {
            $timelineLanes = max($timelineLanes, ((int)($b['lane'] ?? 0)) + 1);
        }

        $shiftBlocksByCraft = $this->buildShiftBlocksByCraft($shifts);

        $craftMeta = $this->buildCraftMeta($shifts);
        $craftGroups = $this->buildCraftGroupsWithLanes($shiftBlocksByCraft, $craftMeta);
        $laneColumns = $this->buildLaneColumns($craftGroups);


        $layout = $this->computeLayoutForDay(
            $timelineLanes,
            count($laneColumns)
        );

        [$rows, $timelineLaneMaps, $craftLaneMaps] = $this->buildSegmentGrid(
            $timelineBlocks,
            $shiftBlocksByCraft,
            $craftGroups,
            $timelineLanes
        );

        return collect([[
            'day'              => $day,
            'dateLabel'        => $day->format('d.m.Y') . ' (' . $day->translatedFormat('l') . ')',
            'eventCards'       => $eventCards,

            'rows'             => $rows,

            'layout'           => $layout,
            'timelineLanes'    => $timelineLanes,
            'timelineLaneMaps' => $timelineLaneMaps,
            'craftGroups'      => $craftGroups,
            'laneColumns'      => $laneColumns,
            'craftLaneMaps'    => $craftLaneMaps,
            'craftMeta'        => $craftMeta,
        ]]);
    }

    /**
     * Zeit + Timeline sollen minimal sein.
     * Dompdf-wrap killen wir, indem wir Timeline-Breite hart begrenzen.
     */
    private function computeLayoutForDay(int $timelineLanes, int $craftCols): array
    {
        // Zeitspalte sehr klein, aber noch gut lesbar
        $timeCol = 44;

        // Timeline gesamt sehr klein
        $timelineMax = 120;
        if ($craftCols >= 4) $timelineMax = 104;
        if ($craftCols >= 6) $timelineMax = 92;

        // pro Lane min/max
        $timelineCol = (int) floor($timelineMax / max(1, $timelineLanes));
        $timelineCol = max(36, min(52, $timelineCol));

        $timelineMax = $timelineCol * max(1, $timelineLanes);

        return [
            'timeCol' => $timeCol,
            'timelineCol' => $timelineCol,
            'timelineMax' => $timelineMax,
        ];
    }

    /**
     * Baut Rows aus Breakpoints (Start/Ende von allen Blocks).
     * Gap-Segmente werden klein, aktive Segmente skaliert.
     *
     * @return array{0:array,1:array,2:array}
     */
    private function buildSegmentGrid(
        array $timelineBlocks,
        array $shiftBlocksByCraft,
        array $craftGroups,
        int $timelineLanes
    ): array {
        $allShiftBlocksFlat = [];
        foreach ($shiftBlocksByCraft as $blocks) {
            foreach ($blocks as $b) $allShiftBlocksFlat[] = $b;
        }

        $allBlocksFlat = array_merge($timelineBlocks, $allShiftBlocksFlat);

        if (empty($allBlocksFlat)) {
            $rows = [[
                'i' => 0,
                'from' => 8 * 60,
                'to' => 9 * 60,
                'isGap' => true,
                'label' => '08:00 – 09:00',
                't1' => '08:00',
                't2' => '09:00',
                'message' => 'Keine Daten',
                'prominent' => true,
                'heightPx' => $this->gapRowHeightPx,
                'minute' => 8 * 60,
            ]];

            return [$rows, [], []];
        }

        $minMin = null;
        $maxMin = null;
        foreach ($allBlocksFlat as $b) {
            $minMin = $minMin === null ? (int)$b['start'] : min($minMin, (int)$b['start']);
            $maxMin = $maxMin === null ? (int)$b['end']   : max($maxMin, (int)$b['end']);
        }
        if ($maxMin <= $minMin) $maxMin = $minMin + 30;

        $breaks = [$minMin, $maxMin];
        foreach ($allBlocksFlat as $b) {
            $breaks[] = (int)$b['start'];
            $breaks[] = (int)$b['end'];
        }
        $breaks = array_values(array_unique(array_filter($breaks, fn($v) => $v !== null)));
        sort($breaks);

        $segments = [];
        for ($i = 0; $i < count($breaks) - 1; $i++) {
            $from = (int)$breaks[$i];
            $to   = (int)$breaks[$i + 1];
            if ($to <= $from) continue;

            $isActive = $this->anyBlockOverlaps($allBlocksFlat, $from, $to);

            $segments[] = [
                'from' => $from,
                'to' => $to,
                'isActive' => $isActive,
            ];
        }

        $merged = [];
        foreach ($segments as $seg) {
            if (empty($merged)) { $merged[] = $seg; continue; }
            $lastIdx = count($merged) - 1;
            $last = $merged[$lastIdx];

            if (!$last['isActive'] && !$seg['isActive'] && $last['to'] === $seg['from']) {
                $merged[$lastIdx]['to'] = $seg['to'];
                continue;
            }
            $merged[] = $seg;
        }

        $totalActiveMinutes = 0;
        $gapCount = 0;

        foreach ($merged as $seg) {
            $dur = $seg['to'] - $seg['from'];
            if ($seg['isActive']) $totalActiveMinutes += $dur;
            else $gapCount++;
        }

        $gapTotalHeight = $gapCount * $this->gapRowHeightPx;
        $availableForActive = max(120, $this->gridTargetHeightPx - $gapTotalHeight);

        $pxPerMinute = $totalActiveMinutes > 0 ? ($availableForActive / $totalActiveMinutes) : 1.0;
        $pxPerMinute = max(0.35, min(2.2, $pxPerMinute));

        $rows = [];
        foreach ($merged as $idx => $seg) {
            $from = $seg['from'];
            $to   = $seg['to'];
            $dur  = $to - $from;

            $isGap = !$seg['isActive'];

            $t1 = $this->minutesToTime($from);
            $t2 = $this->minutesToTime($to);

            $label = $t1 . ' – ' . $t2;

            $height = $isGap
                ? $this->gapRowHeightPx
                : (int)round(max($this->activeRowMinHeightPx, $dur * $pxPerMinute));

            $rows[] = [
                'i' => $idx,
                'from' => $from,
                'to' => $to,
                'isGap' => $isGap,
                'label' => $label,
                't1' => $t1,
                't2' => $t2,
                'minute' => $from,
                'heightPx' => $height,
                'message' => $isGap ? $this->formatGapMessage($dur) : null,
                'prominent' => $isGap ? ($dur >= $this->gapProminentFromMinutes) : false,
            ];
        }

        $timelineLaneMaps = [];
        for ($lane = 0; $lane < $timelineLanes; $lane++) {
            $laneBlocks = array_values(array_filter(
                $timelineBlocks,
                fn($b) => (int)($b['lane'] ?? 0) === $lane
            ));
            $timelineLaneMaps[$lane] = $this->makeSpanMapRows($rows, $laneBlocks);
        }

        $craftLaneMaps = [];
        foreach ($craftGroups as $g) {
            $ck = $g['key'];
            $craftLaneMaps[$ck] = [];

            for ($lane = 0; $lane < $g['lanes']; $lane++) {
                $laneBlocks = array_values(array_filter(
                    $shiftBlocksByCraft[$ck] ?? [],
                    fn($b) => (int)($b['lane'] ?? 0) === $lane
                ));
                $craftLaneMaps[$ck][$lane] = $this->makeSpanMapRows($rows, $laneBlocks);
            }
        }

        return [$rows, $timelineLaneMaps, $craftLaneMaps];
    }

    private function anyBlockOverlaps(array $blocks, int $from, int $to): bool
    {
        foreach ($blocks as $b) {
            $s = (int)$b['start'];
            $e = (int)$b['end'];
            if ($e <= $from) continue;
            if ($s >= $to) continue;
            return true;
        }
        return false;
    }

    /* ----------------------------- EVENTS ----------------------------- */

    private function buildEventCardsCompact(Collection $events): array
    {
        $cards = [];

        /** @var Event $e */
        foreach ($events as $e) {
            $start = Carbon::parse($e->start_time);
            $end   = Carbon::parse($e->end_time);

            $rawColor = $e->event_type?->hex_code ?? null;
            $typeColor = $this->normalizeHexColor($rawColor) ?? '#6366f1';

            $cards[] = [
                'title'       => $e->eventName ?: ($e->name ?? 'Event'),
                'time'        => sprintf('%s – %s', $start->format('H:i'), $end->format('H:i')),
                'description' => trim((string)($e->description ?? '')) ?: null,
                'color'       => $typeColor,
                'bg'          => $this->mixWithWhite($typeColor, 0.93),
                'room'        => $e->room->name ?? null,
            ];
        }

        usort($cards, fn($a, $b) => strcmp($a['time'], $b['time']));
        return $cards;
    }

    private function formatGapMessage(int $minutes): string
    {
        if ($minutes >= 60) {
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;
            if ($m === 0) return $h . 'h frei';
            return $h . 'h ' . $m . 'min frei';
        }
        return $minutes . 'min frei';
    }

    /* ----------------------------- TIMELINES ----------------------------- */

    private function buildTimelineBlocks(Collection $events): array
    {
        $blocks = [];

        foreach ($events as $e) {
            foreach (($e->timelines ?? []) as $t) {
                $startStr = $t->start ?? ($t['start'] ?? null);
                $endStr   = $t->end ?? ($t['end'] ?? null);
                if (!$startStr || !$endStr) continue;

                $start = $this->timeToMinutes($startStr);
                $end   = $this->timeToMinutesWithOvernight($startStr, $endStr);

                $blocks[] = [
                    'start' => $start,
                    'end'   => $end,
                    'title' => trim((string)($t->description ?? ($t['description'] ?? ''))) ?: 'Timeline',
                    'meta'  => sprintf('%s – %s', $startStr, $endStr),
                    'color' => '#16a34a',
                    'bg'    => $this->mixWithWhite('#16a34a', 0.93),
                ];
            }
        }

        usort($blocks, fn($a, $b) => $a['start'] <=> $b['start']);

        $laneEnds = [];
        foreach ($blocks as &$b) {
            $assigned = null;
            foreach ($laneEnds as $li => $lend) {
                if ($lend <= $b['start']) { $assigned = $li; break; }
            }
            if ($assigned === null) $assigned = count($laneEnds);
            $b['lane'] = $assigned;
            $laneEnds[$assigned] = $b['end'];
            ksort($laneEnds);
        }
        unset($b);

        return $blocks;
    }

    /* ----------------------------- SHIFTS ----------------------------- */

    private function buildShiftBlocksByCraft(Collection $shifts): array
    {
        $byCraft = [];

        /** @var Shift $s */
        foreach ($shifts as $s) {
            $craftKey = $this->craftKey($s);

            $startStr = (string)$s->start;
            $endStr   = (string)$s->end;

            $start = $this->timeToMinutes($startStr);
            $end   = $this->timeToMinutesWithOvernight($startStr, $endStr);

            $people  = $this->collectShiftPeople($s);
            $summary = $this->buildShiftQualificationSummary($s);

            $byCraft[$craftKey][] = [
                'start'   => $start,
                'end'     => $end,
                'startStr' => $startStr,
                'endStr'  => $endStr,
                'title'   => $startStr . ' – ' . $endStr,
                'displayTitle' => $startStr . ' – ' . $endStr,
                'meta'    => trim((string)($s->description ?? '')) ?: null,
                'people'  => $people,
                'qualSummary'  => $summary,
                'shiftId' => $s->id ?? null,
                'lane'    => 0,
                'room'    => $s->room?->name ?? null,
            ];
        }

        foreach ($byCraft as $ck => &$blocks) {
            usort($blocks, fn($a, $b) => $a['start'] <=> $b['start']);

            $laneEnds = [];
            foreach ($blocks as &$b) {
                $assigned = null;
                foreach ($laneEnds as $li => $lend) {
                    if ($lend <= $b['start']) { $assigned = $li; break; }
                }
                if ($assigned === null) $assigned = count($laneEnds);
                $b['lane'] = $assigned;
                $laneEnds[$assigned] = $b['end'];
                ksort($laneEnds);
            }
            unset($b);
        }
        unset($blocks);

        return $byCraft;
    }

    private function collectShiftPeople($shift): array
    {
        $people = [];
        $neededByQid = $this->neededCountByQualificationId($shift);

        $make = function (string $type, string $name, $pivot) use (&$people, $neededByQid): void {
            $name = trim($name) !== '' ? trim($name) : ucfirst($type);

            $count = (int)($pivot?->shift_count ?? 1);
            if ($count <= 0) $count = 1;

            $qid = (int)($pivot?->shift_qualification_id ?? 0);
            $needed = $qid > 0 ? (int)($neededByQid[$qid] ?? 0) : 0;

            $qualName = $this->qualificationLabelFromPivot($pivot) ?? '';

            $people[] = [
                'type'   => $type,
                'name'   => $name,
                'qual'   => $qualName,
                'count'  => $count,
                'needed' => $needed,
                'qid'    => $qid,
            ];
        };

        /** @var User $u */
        foreach (($shift->users ?? []) as $u) {
            $n = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            $make('user', $n !== '' ? $n : ('User #' . ($u->id ?? '')), $u->pivot ?? null);
        }

        /** @var Freelancer $f */
        foreach (($shift->freelancer ?? []) as $f) {
            $n = trim(($f->first_name ?? '') . ' ' . ($f->last_name ?? ''));
            if ($n === '' && isset($f->display_name)) $n = (string)$f->display_name;
            if ($n === '' && isset($f->name)) $n = (string)$f->name;

            $make('freelancer', $n !== '' ? $n : ('Freelancer #' . ($f->id ?? '')), $f->pivot ?? null);
        }

        /** @var ServiceProvider $sp */
        foreach (($shift->serviceProvider ?? []) as $sp) {
            $n = trim((string)($sp->provider_name ?? ''));
            if ($n === '' && isset($sp->name)) $n = (string)$sp->name;

            $make('service', $n !== '' ? $n : ('Service #' . ($sp->id ?? '')), $sp->pivot ?? null);
        }

        return $people;
    }

    private function qualificationLabelFromPivot($pivot): ?string
    {
        if (!$pivot) return null;

        $sd = trim((string)($pivot->short_description ?? ''));
        if ($sd !== '') return $sd;

        $qid = $pivot->shift_qualification_id ?? null;
        if ($qid && isset($this->qualificationNameById[(int)$qid])) {
            return $this->qualificationNameById[(int)$qid];
        }

        $abbr = trim((string)($pivot->craft_abbreviation ?? ''));
        if ($abbr !== '') return $abbr;

        return null;
    }

    /** @return array<int,int> [qualificationId => neededCount] */
    private function neededCountByQualificationId($shift): array
    {
        $map = [];

        foreach (($shift->shiftsQualifications ?? []) as $sq) {
            $qid = (int)($sq->shift_qualification_id ?? 0);
            if ($qid <= 0) continue;

            $map[$qid] = (int)($sq->value ?? 0);
        }

        return $map;
    }

    private function buildCraftMeta(Collection $shifts): array
    {
        $meta = [];

        foreach ($shifts as $s) {
            $craft = $s->craft;
            if (!$craft) continue;

            $key = strtoupper($craft->abbreviation ?? ('CRAFT_' . $craft->id));
            if (isset($meta[$key])) continue;

            $color = $this->normalizeHexColor($craft->color) ?? '#0ea5e9';

            $meta[$key] = [
                'key'      => $key,
                'name'     => $craft->name ?? $key,
                'abbr'     => $craft->abbreviation ?? $key,
                'position' => (int)($craft->position ?? 9999),
                'color'    => $color,
                'bg'       => $this->mixWithWhite($color, 0.93),
                'soft'     => $this->mixWithWhite($color, 0.96),
            ];
        }

        return $meta;
    }

    private function buildCraftGroupsWithLanes(array $shiftBlocksByCraft, array $craftMeta): array
    {
        $groups = [];

        foreach ($shiftBlocksByCraft as $craftKey => $blocks) {
            $laneCount = 1;
            foreach ($blocks as $b) {
                $laneCount = max($laneCount, ((int)($b['lane'] ?? 0)) + 1);
            }

            $m = $craftMeta[$craftKey] ?? null;

            $groups[] = [
                'key'   => $craftKey,
                'label' => $m['abbr'] ?? $craftKey,
                'lanes' => $laneCount,
                'color' => $m['color'] ?? '#0ea5e9',
                'bg'    => $m['soft'] ?? $this->mixWithWhite('#0ea5e9', 0.96),
                'pos'   => $m['position'] ?? 9999,
            ];
        }

        usort($groups, fn($a, $b) => ($a['pos'] <=> $b['pos']) ?: strcmp($a['label'], $b['label']));
        return $groups;
    }

    private function buildLaneColumns(array $craftGroups): array
    {
        $cols = [];
        foreach ($craftGroups as $g) {
            for ($lane = 0; $lane < $g['lanes']; $lane++) {
                $cols[] = [
                    'craftKey' => $g['key'],
                    'lane'     => $lane,
                    'label'    => '',
                ];
            }
        }
        return $cols;
    }

    /* ----------------------------- SPAN MAP ----------------------------- */

    private function makeSpanMapRows(array $rows, array $blocks): array
    {
        $map = [];

        usort($blocks, fn($a, $b) => $a['start'] <=> $b['start']);

        foreach ($blocks as $b) {
            $startIndex = $this->rowIndexForMinute($rows, (int)$b['start']);
            $endIndex   = $this->rowIndexForMinute($rows, max((int)$b['start'], (int)$b['end'] - 1));

            if ($startIndex === null || $endIndex === null) continue;
            if ($endIndex < $startIndex) continue;

            while ($endIndex >= $startIndex && ($rows[$endIndex]['isGap'] ?? false)) {
                $endIndex--;
            }
            if ($endIndex < $startIndex) continue;

            if (isset($map[$startIndex])) continue;

            $spanHeight = 0;
            for ($i = $startIndex; $i <= $endIndex; $i++) {
                $spanHeight += (int)($rows[$i]['heightPx'] ?? 16);
            }

            $map[$startIndex] = [
                'rowspan'  => ($endIndex - $startIndex + 1),
                'heightPx' => $spanHeight,
                'data'     => $b,
            ];

            for ($i = $startIndex + 1; $i <= $endIndex; $i++) {
                $map[$i] = ['skip' => true];
            }
        }

        return $map;
    }

    private function rowIndexForMinute(array $rows, int $minute): ?int
    {
        foreach ($rows as $idx => $r) {
            if ($minute >= $r['from'] && $minute < $r['to']) return $idx;
        }
        $last = end($rows);
        if ($last && $minute >= $last['to']) return count($rows) - 1;
        return null;
    }

    /* ----------------------------- PAGE PACKING ----------------------------- */

    private function packChunksIntoPages(Collection $chunks): array
    {
        $pages = [];
        $current = [];

        foreach ($chunks as $chunk) {
            if (count($current) >= $this->maxChunksPerPage) {
                $pages[] = $current;
                $current = [];
            }
            $current[] = $chunk;
        }
        if (!empty($current)) $pages[] = $current;

        return $pages;
    }

    /* ----------------------------- HELPERS ----------------------------- */

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return $h * 60 + $m;
    }

    private function minutesToTime(int $minutes): string
    {
        $h = intdiv($minutes, 60) % 24;
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    private function timeToMinutesWithOvernight(string $start, string $end): int
    {
        $s = $this->timeToMinutes($start);
        $e = $this->timeToMinutes($end);
        if ($e <= $s) $e += 24 * 60;
        return $e;
    }

    private function craftKey($shift): string
    {
        return strtoupper($shift->craft?->abbreviation ?? ('CRAFT_' . $shift->craft_id));
    }

    private function shiftDate($shift): Carbon
    {
        $raw = $shift->getRawOriginal('start_date') ?? $shift->start_date;

        try {
            return Carbon::createFromFormat('d. M Y', $raw)->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::parse($raw)->startOfDay();
        }
    }

    private function normalizeHexColor(?string $color): ?string
    {
        if (!$color) return null;

        $c = trim($color);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $c)) return $c;
        if (preg_match('/^([0-9a-fA-F]{6})$/', $c)) return '#' . $c;

        return null;
    }

    private function mixWithWhite(string $hex, float $mix = 0.93): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) return '#f3f4f6';

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r2 = (int) round($r * (1 - $mix) + 255 * $mix);
        $g2 = (int) round($g * (1 - $mix) + 255 * $mix);
        $b2 = (int) round($b * (1 - $mix) + 255 * $mix);

        return sprintf('#%02x%02x%02x', $r2, $g2, $b2);
    }
}
