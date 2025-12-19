<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">

    <style>
        /* ✅ Drucker-sicherer Rand (mm statt px) */
        @page { margin: 10mm 10mm 5mm 10mm; }  /* oben/rechts/unten/links */

        :root{
            --ink:#0b1220;
            --muted:#64748b;
            --line:#cbd5e1;
            --lineStrong:#0f172a;
            --paper:#ffffff;
            --head:#f1f5f9;
            --gap:#f8fafc;
            --radius: 10px;
        }

        /* ✅ 300dpi: pt statt px, klein & lesbar */
        /* ✅ Browser/Dompdf Default-Body-Margins ausschalten (sonst “doppelt”/komisch) */
        body{
            margin: 0;
            padding: 0;

            font-family: DejaVu Sans, sans-serif;
            font-size: 6.35pt;
            color: var(--ink);
            background: var(--paper);
            line-height: 1.20;
        }

        .page:last-child{ page-break-after: auto; }

        /* ---------- HEADER (flach) ---------- */
        .hdr{
            border: 1px solid var(--lineStrong);
            padding: 6px 9px;       /* +20% vs vorher */
            margin-bottom: 8px;     /* +20% */
            page-break-inside: avoid;
        }

        .hdrRow{ display: table; width: 100%; }
        .hdrL, .hdrR{ display: table-cell; vertical-align: top; }
        .hdrR{ width: 20%; text-align: right; }

        .hdrTitle{
            font-size: 9.8pt;
            font-weight: 900;
            letter-spacing: -0.25px;
            color: #0f172a;
            margin: 0;
        }

        .hdrMetaLine{
            margin-top: 3px;        /* +20% */
            font-size: 6.8pt;
            font-weight: 900;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sep{ margin: 0 4px; color: rgba(15,23,42,0.35); }
        .muted{ color: var(--muted); }

        .exportMini{ padding: 4px 6px; } /* +20% */
        .exportMiniTop{
            font-size: 5.7pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.55px;
            color: var(--muted);
        }
        .exportMiniTime{
            margin-top: 1px;
            font-size: 6.1pt;
            font-weight: 900;
            color: #0f172a;
            white-space: nowrap;
        }

        /* ---------- TEAM + GEWERKE nebeneinander ---------- */
        .hdrBottom{
            margin-top: 6px;        /* +20% */
            border-top: 1px solid rgba(15,23,42,0.12);
            padding-top: 6px;       /* +20% */
        }
        .hdrBottomRow{ display: table; width: 100%; }
        .hdrBottomL, .hdrBottomR{ display: table-cell; vertical-align: top; }
        .hdrBottomL{ width: 46%; padding-right: 9px; } /* +20% */
        .hdrBottomR{ width: 54%; padding-left: 9px; border-left: 1px solid rgba(15,23,42,0.10); } /* +20% */

        /* TEAM ultra-kompakt */
        .roleList{ margin: 0; padding: 0; list-style: none; }
        .roleItem{
            border: 1px solid rgba(15,23,42,0.10);
            padding: 4px 6px;       /* +20% */
            margin-bottom: 4px;     /* +20% */
            overflow: hidden;
        }
        .roleItem:last-child{ margin-bottom: 0; }

        .roleLine{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 5.4pt;
            font-weight: 900;
            color: #0f172a;
        }
        .rolePeople{
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 5.2pt;
            font-weight: 900;
            color: var(--muted);
        }

        /* GEWERKE kompakt */
        .legMiniGrid{ display: table; width: 100%; border-spacing: 0 4px; } /* +20% */
        .legMiniRow{ display: table-row; }
        .legMiniCell{
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 9px;     /* +20% */
        }
        .legMiniCell:last-child{ padding-right: 0; }

        .legMiniCard{
            border: 1px solid rgba(15,23,42,0.12);
            padding: 4px 6px;       /* +20% */
            overflow: hidden;
        }

        .legMiniTop{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .legDot{
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            border: 1px solid rgba(15,23,42,0.22);
            margin-right: 5px;
            vertical-align: -1px;
        }

        .legName{
            font-weight: 900;
            font-size: 5.6pt;
            color: #0f172a;
        }

        .legQuals{
            margin-top: 1px;
            font-size: 5.2pt;
            font-weight: 900;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .page{
            page-break-after: always;
            page-break-inside: avoid;
        }

        /* Dompdf: thead/tbody Separation-Bug */
        table.grid thead{ display: table-row-group; }
        table.grid tr{ page-break-inside: avoid; }

        .day{
            border: 1px solid var(--lineStrong);
            overflow: hidden;
            margin-bottom: 20px;    /* +20% */
            page-break-inside: avoid;
        }
        .day-head{
            padding: 9px 12px;      /* +20% */
            background: #fde68a;
            border-bottom: 1px solid var(--lineStrong);
            font-weight: 900;
            font-size: 6.8pt;
        }

        /* Events */
        .events{
            padding: 9px 12px 8px 12px; /* +20% */
            background: #fff;
            border-bottom: 1px solid var(--line);
            page-break-inside: avoid;
        }
        .event-grid{
            display: table;
            width: 100%;
            border-spacing: 0 8px;  /* +20% */
        }
        .event-row{ display: table-row; }
        .event-cell{
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 11px;    /* +20% */
        }
        .event-cell:last-child{ padding-right: 0; }

        .event-card{
            border: 1px solid rgba(15,23,42,0.18);
            overflow: hidden;
        }
        .event-body{ padding: 8px 10px; } /* +20% */
        .event-name{ font-weight: 900; font-size: 6.4pt; margin-bottom: 2px; } /* margin leicht mitgezogen */
        .event-time{ font-weight: 900; font-size: 5.8pt; color: #0f172a; }
        .event-desc{ margin-top: 4px; color: var(--muted); font-size: 5.4pt; } /* +20% */

        /* Grid */
        table.grid{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #fff;
            page-break-inside: avoid;
        }
        table.grid th, table.grid td{
            border: 1px solid var(--line);
            vertical-align: top;
            padding: 4px 5px;       /* +20% */
        }
        table.grid th{
            background: var(--head);
            text-align: center;
            font-weight: 900;
            font-size: 5.5pt;
            letter-spacing: 0.2px;
            padding: 6px 5px;       /* +20% */
        }

        /* Zeit kompakt */
        .timeRange{ font-weight: 900; font-size: 6.0pt; text-align:center; line-height: 1.05; }
        .timeSub{ color: var(--muted); font-size: 5.4pt; margin-top: 3px; text-align:center; } /* +20% */

        .gapRow td{ background: var(--gap); }
        .gapMsg{
            color: var(--muted);
            font-size: 5.8pt;
            font-weight: 900;
            text-align: center;
            padding: 6px 0;         /* +20% */
        }
        .gapMsgStrong{ color: #0f172a; font-size: 6.2pt; }

        .cellBox{ overflow: hidden; }
        .cellBody{ padding: 6px 8px; } /* +20% */
        .cellTitle{ font-weight: 900; font-size: 6.0pt; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cellMeta{ margin-top: 3px; color: var(--muted); font-size: 5.5pt; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; } /* +20% */

        .shiftSummary{ padding: 5px 0 0 0; } /* +20% */
        .shiftQualLine{
            font-size: 5.4pt;
            font-weight: 900;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .people{ margin-top: 8px; } /* +20% */
        .person{
            padding-top: 5px;       /* +20% */
            margin-top: 5px;        /* +20% */
            border-top: 1px dashed rgba(15,23,42,0.14);
        }
        .p-top{ display: table; width: 100%; }
        .p-left, .p-right{ display: table-cell; vertical-align: top; }
        .p-right{ text-align: right; width: 34%; }

        .p-name{ font-weight: 900; font-size: 5.7pt; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }
        .p-qual{
            display: inline-block;
            margin-top: 3px;        /* +20% */
            padding: 2px 8px;       /* +20% */
            border: 1px solid rgba(15,23,42,0.16);
            font-size: 5.3pt;
            color: var(--muted);
            font-weight: 900;
            background: rgba(255,255,255,0.55);
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .laneHead{
            font-weight: 900;
            font-size: 5.3pt;
            color: var(--muted);
            background: #fafafa;
        }

        /* Timeline Text */
        .tlMeta{ font-weight: 900; font-size: 5.7pt; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .tlTitle{ margin-top:1px; font-weight:900; font-size: 5.3pt; color: var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    </style>
</head>
<body>

@php
    $ph = function(int $h){
        $h = max(8, $h);
        return '<span style="display:block;height:'.$h.'px;line-height:'.$h.'px;font-size:1px;color:transparent;">&nbsp;</span>';
    };
@endphp

@foreach($pages as $page)
    <div class="page">

        {{-- HEADER nur auf Seite 1 --}}
        @if($loop->first)
            @php
                $legend = $meta['legendCrafts'] ?? [];

                $firstDate    = data_get($project, 'first_and_last_event_date.first_event_date') ?? null;
                $lastDate     = data_get($project, 'first_and_last_event_date.last_event_date') ?? null;

                $legendCols = 2;
                $legendRows = array_chunk($legend, $legendCols);

                $rolesRaw = $groupedUsersByRole ?? [];
                $roleCards = [];
                foreach ($rolesRaw as $roleName => $users) {
                    $roleCards[] = [
                        'role' => (string)$roleName,
                        'users' => $users ?? [],
                    ];
                }

                $maxRoleCards = 6;
                $roleCardsVisible = array_slice($roleCards, 0, $maxRoleCards);
                $hiddenRoleCount = max(0, count($roleCards) - count($roleCardsVisible));
            @endphp

            <div class="hdr">
                <div class="hdrRow">
                    <div class="hdrL">
                        <div class="hdrTitle">{{ $project->name }}</div>

                        <div class="hdrMetaLine">
                            @if($firstDate || $lastDate)
                                <span class="muted">Zeitraum:</span>
                                <strong>{{ $firstDate ?? '—' }}</strong>–<strong>{{ $lastDate ?? '—' }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="hdrR">
                        <div class="exportMini">
                            <div class="exportMiniTop">Export</div>
                            @if(($created_by['name'] ?? '') !== '' && !$privacyMode)
                                <span class="muted">Von:</span> <strong>{{ $created_by['name'] }}</strong>
                            @endif
                            <div class="exportMiniTime">{{ $meta['exportedAt']->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                <div class="hdrBottom">
                    <div class="hdrBottomRow">
                        {{-- TEAM --}}
                        <div class="hdrBottomL">
                            @if(!empty($roleCardsVisible))
                                <ul class="roleList">
                                    @foreach($roleCardsVisible as $card)
                                        @php
                                            $users = $card['users'] ?? [];

                                            if ($privacyMode) {
                                                $peopleLine = count($users).' Pers.';
                                            } else {
                                                $entries = [];
                                                foreach ($users as $u) {
                                                    $n = trim((string)($u['full_name'] ?? ''));
                                                    $e = trim((string)($u['email'] ?? ''));

                                                    if ($n === '' && $e === '') continue;

                                                    $label = $n !== '' ? $n : '—';
                                                    if ($e !== '') $label .= ' <'.$e.'>';

                                                    $entries[] = $label;
                                                }
                                                $entries = array_slice($entries, 0, 2);
                                                $peopleLine = !empty($entries) ? implode(' • ', $entries) : '—';
                                            }
                                        @endphp

                                        <li class="roleItem">
                                            <div class="roleLine">{{ $card['role'] }}</div>
                                            <div class="rolePeople">{{ $peopleLine }}</div>
                                        </li>
                                    @endforeach

                                    @if($hiddenRoleCount > 0)
                                        <li class="roleItem">
                                            <div class="roleLine">Weitere Rollen</div>
                                            <div class="rolePeople">+{{ $hiddenRoleCount }}</div>
                                        </li>
                                    @endif
                                </ul>
                            @else
                                <div class="roleItem">
                                    <div class="roleLine">—</div>
                                    <div class="rolePeople">Keine Rollen</div>
                                </div>
                            @endif
                        </div>

                        {{-- GEWERKE --}}
                        <div class="hdrBottomR">
                            @if(!empty($legend))
                                <div class="legMiniGrid">
                                    @foreach($legendRows as $r)
                                        <div class="legMiniRow">
                                            @foreach($r as $c)
                                                <div class="legMiniCell">
                                                    <div class="legMiniCard" style="background: {{ $c['bg'] }};">
                                                        <div class="legMiniTop">
                                                            <span class="legName">
                                                                {{ trim((string)($c['abbr'] ?? '')) !== '' ? ($c['abbr'].' ') : '' }}{{ $c['name'] }}
                                                            </span>
                                                        </div>

                                                        <div class="legQuals">
                                                            @php
                                                                $quals = $c['quals'] ?? [];
                                                                $quals = array_slice($quals, 0, 3);
                                                            @endphp
                                                            {{ !empty($quals) ? implode(' • ', $quals) : '—' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @for($i = count($r); $i < $legendCols; $i++)
                                                <div class="legMiniCell">{!! $ph(8) !!}</div>
                                            @endfor
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="roleItem">
                                    <div class="roleLine">—</div>
                                    <div class="rolePeople">Keine Gewerke</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- pro Seite: 1 Tag --}}
        @foreach($page as $chunk)
            @php
                $layout    = $chunk['layout'] ?? ['timeCol'=>44,'timelineCol'=>42,'timelineMax'=>84];
                $timeW     = (int)($layout['timeCol'] ?? 44);
                $tlW       = (int)($layout['timelineCol'] ?? 42);
                $tlCols    = (int)($chunk['timelineLanes'] ?? 1);
                $craftCols = count($chunk['laneColumns'] ?? []);
            @endphp

            <div class="day">
                <div class="day-head">{{ $chunk['dateLabel'] }}</div>

                {{-- EVENTS --}}
                <div class="events">
                    @php $cards = $chunk['eventCards'] ?? []; @endphp
                    @if(!empty($cards))
                        @php $pairs = array_chunk($cards, 2); @endphp

                        <div class="event-grid">
                            @foreach($pairs as $pair)
                                <div class="event-row">
                                    @foreach($pair as $card)
                                        <div class="event-cell">
                                            <div class="event-card">
                                                <div class="event-body" style="background: {{ $card['bg'] }};">
                                                    <div class="event-name">{{ $card['title'] }}</div>
                                                    <div class="event-time">{{ $card['time'] }}</div>
                                                    <div class="event-time">{{ $card['room'] }}</div>

                                                    @if($card['description'])
                                                        <div class="event-desc">{{ $card['description'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if(count($pair) === 1)
                                        <div class="event-cell">{!! $ph(20) !!}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="color: #64748b; font-weight: 900;">Keine Events</div>
                    @endif
                </div>

                {{-- GRID --}}
                <table class="grid">

                    {{-- ✅ Dompdf-wrap verhindern: harte col widths für Zeit + Timeline --}}
                    <colgroup>
                        <col style="width: {{ $timeW }}px;">
                        @for($tl = 0; $tl < $tlCols; $tl++)
                            <col style="width: {{ $tlW }}px;">
                        @endfor
                        @for($i = 0; $i < $craftCols; $i++)
                            <col>
                        @endfor
                    </colgroup>

                    <thead>
                    <tr>
                        <th rowspan="2">Zeit</th>
                        <th colspan="{{ $tlCols }}">Timeline</th>

                        @if(!empty($chunk['craftGroups']))
                            @foreach($chunk['craftGroups'] as $g)
                                <th colspan="{{ $g['lanes'] }}">{{ $g['label'] }}</th>
                            @endforeach
                        @else
                            <th rowspan="2">Keine Schichten</th>
                        @endif
                    </tr>

                    <tr>
                        @for($tl = 0; $tl < $tlCols; $tl++)
                            <th class="laneHead"></th>
                        @endfor

                        @if(!empty($chunk['laneColumns']))
                            @foreach($chunk['laneColumns'] as $col)
                                <th class="laneHead"></th>
                            @endforeach
                        @endif
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($chunk['rows'] as $row)
                        @php $h = (int)($row['heightPx'] ?? 16); @endphp

                        @if($row['isGap'])
                            <tr class="gapRow" style="height: {{ $h }}px;">
                                <td>
                                    <div class="timeRange">{{ $row['t1'] ?? $row['label'] }}</div>
                                    <div class="timeSub">-</div>
                                    <div class="timeSub">{{ $row['t2'] ?? '' }}</div>
                                </td>

                                <td colspan="{{ $tlCols + $craftCols }}">
                                    <div class="gapMsg {{ !empty($row['prominent']) ? 'gapMsgStrong' : '' }}">
                                        {{ $row['message'] }}
                                    </div>
                                </td>
                            </tr>
                            @continue
                        @endif

                        <tr style="height: {{ $h }}px;">
                            {{-- TIME --}}
                            <td>
                                <div class="timeRange">{{ $row['t1'] ?? $row['label'] }}</div>
                                <div class="timeSub">-</div>
                                <div class="timeSub">{{ $row['t2'] ?? '' }}</div>
                            </td>

                            {{-- TIMELINE Lanes --}}
                            @for($tl = 0; $tl < $tlCols; $tl++)
                                @php
                                    $tmap = $chunk['timelineLaneMaps'][$tl] ?? [];
                                    $tm = $tmap[$row['i']] ?? null;
                                @endphp

                                @if($tm && !empty($tm['skip']))
                                @elseif($tm)
                                    <td rowspan="{{ $tm['rowspan'] }}" style="background: {{ $tm['data']['bg'] }};">
                                        <div class="cellBox">
                                            <div class="cellBody">
                                                <div class="tlMeta">{{ $tm['data']['meta'] }}</div>
                                                @if(!empty($tm['data']['title']) && $tm['data']['title'] !== $tm['data']['meta'])
                                                    <div class="tlTitle">{{ $tm['data']['title'] }}</div>
                                                @endif
                                            </div>

                                            {!! $ph((int)($tm['heightPx'] ?? $h)) !!}
                                        </div>
                                    </td>
                                @else
                                    <td>{!! $ph($h) !!}</td>
                                @endif
                            @endfor

                            {{-- CRAFT LANES --}}
                            @if(!empty($chunk['laneColumns']))
                                @foreach($chunk['laneColumns'] as $col)
                                    @php
                                        $craftKey = $col['craftKey'];
                                        $lane = $col['lane'];

                                        $map = $chunk['craftLaneMaps'][$craftKey][$lane] ?? [];
                                        $cm = $map[$row['i']] ?? null;

                                        $craft = $chunk['craftMeta'][$craftKey] ?? null;
                                        $cBg = $craft['bg'] ?? '#e0f2fe';
                                    @endphp

                                    @if($cm && !empty($cm['skip']))
                                        {{-- skip --}}
                                    @elseif($cm)
                                        <td rowspan="{{ $cm['rowspan'] }}" style="background: {{ $cBg }};">
                                            <div class="cellBox">
                                                <div class="cellBody">
                                                    <div class="cellTitle">{{ $craft['key'] }}</div>
                                                    <div class="cellTitle">{{ $cm['data']['displayTitle'] ?? $cm['data']['title'] }}</div>

                                                    @if(!empty($cm['data']['room']))
                                                        <div class="cellMeta">{{ $cm['data']['room'] }}</div>
                                                    @endif
                                                    @if(!empty($cm['data']['meta']))
                                                        <div class="cellMeta">{{ $cm['data']['meta'] }}</div>
                                                    @endif

                                                    {{-- Soll/Ist Qualifikationen --}}
                                                    @if(!empty($cm['data']['qualSummary']))
                                                        <div class="shiftSummary">
                                                            @foreach($cm['data']['qualSummary'] as $q)
                                                                @php
                                                                    $sum = (int)($q['sum'] ?? 0);
                                                                    $needed = (int)($q['needed'] ?? 0);
                                                                    $neededText = $needed > 0 ? $needed : 0;
                                                                @endphp

                                                                <div class="shiftQualLine">
                                                                    {{ $sum }} / {{ $neededText }} {{ $q['name'] }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    {{-- People --}}
                                                    @if(!empty($cm['data']['people']) && !$privacyMode)
                                                        <div class="people">
                                                            @foreach($cm['data']['people'] as $p)
                                                                @php $qual = trim((string)($p['qual'] ?? '')); @endphp

                                                                <div class="person">
                                                                    <div class="p-top">
                                                                        <div class="p-left">
                                                                            <span class="p-name">{{ $p['name'] }}</span>
                                                                        </div>

                                                                        <div class="p-right">
                                                                            <span class="p-qual">
                                                                                @if($qual !== '')
                                                                                    {{ $qual }}
                                                                                @else
                                                                                    {!! $ph(10) !!}
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>

                                                {!! $ph((int)($cm['heightPx'] ?? $h)) !!}
                                            </div>
                                        </td>
                                    @else
                                        <td>{!! $ph($h) !!}</td>
                                    @endif
                                @endforeach
                            @else
                                <td>{!! $ph($h) !!}</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endforeach

</body>
</html>
