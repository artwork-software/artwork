<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">

    <style>
        @page { margin: 16px 16px 18px 16px; }

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

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: var(--ink);
            background: var(--paper);
            line-height: 1.28;
        }

        .page{ page-break-after: always; }
        .page:last-child{ page-break-after: auto; }

        .topbar{
            border: 1px solid var(--lineStrong);
            padding: 10px 12px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .topbar-row{ display: table; width: 100%; }
        .topbar-left, .topbar-right{ display: table-cell; vertical-align: top; }
        .topbar-right{ text-align: right; width: 35%; }

        .project-title{ font-size: 14px; font-weight: 900; letter-spacing: -0.2px; }
        .project-sub{ margin-top: 3px; color: var(--muted); font-size: 9px; }

        .meta-pill{
            display: inline-block;
            border: 1px solid var(--lineStrong);
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: 900;
            margin-left: 6px;
        }

        .day{
            border: 1px solid var(--lineStrong);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .day-head{
            padding: 8px 12px;
            background: #fde68a;
            border-bottom: 1px solid var(--lineStrong);
            font-weight: 900;
            font-size: 10px;
        }

        /* Events */
        .events{
            padding: 10px 12px 6px 12px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            page-break-inside: avoid;
        }
        .events-title{
            font-weight: 900;
            font-size: 8px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .event-grid{
            display: table;
            width: 100%;
            border-spacing: 0 8px;
        }
        .event-row{ display: table-row; }
        .event-cell{
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .event-cell:last-child{ padding-right: 0; }

        .event-card{
            border: 1px solid rgba(15,23,42,0.18);
            overflow: hidden;
        }
        .event-body{ padding: 8px 10px; }
        .event-name{ font-weight: 900; font-size: 10px; margin-bottom: 2px; }
        .event-time{ font-weight: 900; font-size: 9px; color: #0f172a; }
        .event-desc{ margin-top: 5px; color: var(--muted); font-size: 8px; }

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
            padding: 3px 4px;
        }
        table.grid th{
            background: var(--head);
            text-align: center;
            font-weight: 900;
            font-size: 8px;
            letter-spacing: 0.2px;
            padding: 6px 4px;
        }

        .col-time{ width: 90px; text-align: center; }
        .col-timeline{ width: 240px; }

        .timeRange{ font-weight: 900; font-size: 8.6px; }
        .timeSub{ color: var(--muted); font-size: 7.8px; margin-top: 2px; }

        .gapRow td{ background: var(--gap); }
        .gapMsg{
            color: var(--muted);
            font-size: 8px;
            font-weight: 900;
            text-align: center;
            padding: 5px 0;
        }
        .gapMsgStrong{ color: #0f172a; font-size: 8.7px; }

        .cellBox{
            overflow: hidden;
        }
        .cellBody{ padding: 6px 6px; }
        .cellTitle{ font-weight: 900; font-size: 8.7px; }
        .cellMeta{ margin-top: 2px; color: var(--muted); font-size: 8px; }

        .people{ margin-top: 6px; }
        .person{
            padding-top: 4px;
            margin-top: 4px;
            border-top: 1px dashed rgba(15,23,42,0.14);
        }
        .p-top{ display: table; width: 100%; }
        .p-left, .p-right{ display: table-cell; vertical-align: top; }
        .p-right{ text-align: right; width: 34%; }

        .p-name{ font-weight: 900; font-size: 8px; }
        .p-qual{
            display: inline-block;
            margin-top: 2px;
            padding: 1px 6px;
            border-radius: 999px;
            border: 1px solid rgba(15,23,42,0.16);
            font-size: 7.3px;
            color: var(--muted);
            font-weight: 900;
            background: rgba(255,255,255,0.55);
        }

        .laneHead{
            font-weight: 900;
            font-size: 7.5px;
            color: var(--muted);
            background: #fafafa;
        }
    </style>
</head>
<body>

@php
    // Platzhalter für leere Zellen – Höhe kommt aus row->heightPx
    $ph = function(int $h){
        $h = max(8, $h);
        return '<span style="display:block;height:'.$h.'px;line-height:'.$h.'px;font-size:1px;color:transparent;">&nbsp;</span>';
    };
@endphp

<script type="text/php">
    if ( isset($pdf) ) {

      $size = 6;
      $color = array(0,0,0);
      if (class_exists('Font_Metrics')) {
        $font = Font_Metrics::get_font("helvetica");
        $text_height = Font_Metrics::get_font_height($font, $size);
        $width = Font_Metrics::get_text_width("Page 1 of 2", $font, $size);
      } elseif (class_exists('Dompdf\\FontMetrics')) {
        $font = $fontMetrics->getFont("helvetica");
        $text_height = $fontMetrics->getFontHeight($font, $size);
        $width = $fontMetrics->getTextWidth("Page 1 of 2", $font, $size);
      }

      $foot = $pdf->open_object();

      $w = $pdf->get_width();
      $h = $pdf->get_height();

      $y = $h - $text_height - 24;
      $pdf->line(16, $y, $w - 16, $y, $color, 0.5);

      $pdf->close_object();
      $pdf->add_object($foot, "all");

      $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
      $pdf->page_text($w / 2 - $width / 2, $y, $text, $font, $size, $color);

    }
</script>

@foreach($pages as $page)
    <div class="page">

        {{-- Header NUR auf Seite 1 (zusammen mit Tag 1) --}}
        @if($loop->first)
            <div class="topbar">
                <div class="topbar-row">
                    <div class="topbar-left">
                        <div class="project-title">{{ $project->name }}</div>
                        <div class="project-sub">Daily Shift Plan • Zeiten zusammengezogen • 1 Tag = 1 Seite</div>
                    </div>
                    <div class="topbar-right">
                        <span class="meta-pill">Export</span>
                        <span class="meta-pill">{{ $meta['exportedAt']->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>
        @endif

        @foreach($page as $chunk)
            <div class="day">
                <div class="day-head">{{ $chunk['dateLabel'] }}</div>

                {{-- EVENTS --}}
                <div class="events">
                    <div class="events-title">Events</div>

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

                                                    @if($card['description'])
                                                        <div class="event-desc">{{ $card['description'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if(count($pair) === 1)
                                        <div class="event-cell">{!! $ph(26) !!}</div>
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
                    <thead>
                    <tr>
                        <th class="col-time" rowspan="2">Zeit</th>
                        <th class="col-timeline" rowspan="2">Timeline</th>

                        @if(!empty($chunk['craftGroups']))
                            @foreach($chunk['craftGroups'] as $g)
                                <th colspan="{{ $g['lanes'] }}">{{ $g['label'] }}</th>
                            @endforeach
                        @else
                            <th rowspan="2">Keine Schichten</th>
                        @endif
                    </tr>

                    <tr>
                        @if(!empty($chunk['laneColumns']))
                            @foreach($chunk['laneColumns'] as $col)
                                <th class="laneHead">{{ $col['label'] }}</th>
                            @endforeach
                        @endif
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($chunk['rows'] as $row)
                        @php $h = (int)($row['heightPx'] ?? 16); @endphp

                        @if($row['isGap'])
                            <tr class="gapRow" style="height: {{ $h }}px;">
                                <td class="col-time">
                                    <div class="timeRange">{{ $row['label'] }}</div>
                                </td>
                                <td colspan="{{ 1 + (count($chunk['laneColumns'] ?? [])) }}">
                                    <div class="gapMsg {{ !empty($row['prominent']) ? 'gapMsgStrong' : '' }}">
                                        {{ $row['message'] }}
                                    </div>
                                </td>
                            </tr>
                            @continue
                        @endif

                        <tr style="height: {{ $h }}px;">
                            {{-- TIME (nur Start-Ende Segment) --}}
                            <td class="col-time">
                                <div class="timeRange">{{ $row['label'] }}</div>
                            </td>

                            {{-- TIMELINE --}}
                            @php $tm = $chunk['timelineMap'][$row['i']] ?? null; @endphp
                            @if($tm && !empty($tm['skip']))
                                {{-- skip --}}
                            @elseif($tm)
                                <td class="col-timeline" rowspan="{{ $tm['rowspan'] }}"  style="background: {{ $tm['data']['bg'] }};">
                                    <div class="cellBox">
                                        <div class="cellBody">
                                            <div class="cellTitle">{{ $tm['data']['meta'] }}</div>
                                        </div>
                                    </div>
                                </td>
                            @else
                                <td class="col-timeline">{!! $ph($h) !!}</td>
                            @endif

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
                                        <td rowspan="{{ $cm['rowspan'] }}"  style="background: {{ $cBg }};">
                                            <div class="cellBox">
                                                <div class="cellBody" >
                                                    <div class="cellTitle">{{ $cm['data']['displayTitle'] ?? $cm['data']['title'] }}</div>

                                                    @if(!empty($cm['data']['meta']))
                                                        <div class="cellMeta">{{ $cm['data']['meta'] }}</div>
                                                    @endif

                                                    @if(!empty($cm['data']['people']))
                                                        <div class="people">
                                                            @foreach($cm['data']['people'] as $p)
                                                                <div class="person">
                                                                    <div class="p-top">
                                                                        <div class="p-left">
                                                                            <span class="p-name">{{ $p['name'] }}</span>
                                                                        </div>
                                                                        <div class="p-right">
                                                                            <span class="p-qual">
                                                                                @if(!empty($p['qual'])) {{ $p['qual'] }} @endif
                                                                                @if(empty($p['count']) && empty($p['qual'])) {!! $ph(10) !!} @endif
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
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
