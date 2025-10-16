<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Kalender</title>
    <style>
        *,
        *:before,
        *:after {
            box-sizing: border-box;
        }

        footer {
            bottom: 0;
            position: absolute;
        }

        @media print {
            table {
                width: 100%;
                transform: scale(0.8); /* Anpassen nach Bedarf */
            }
            th, td {
                font-size: 10px; /* Kleinere Schriftgröße */
            }
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
        }

        td, th {
            border: 1px solid black;
            text-align: left;
            padding: 2px; /* Reduzierte Polsterung */
            font-size: 10px; /* Kleinere Schriftgröße */
            word-wrap: break-word; /* Umbruch bei langen Wörtern */
        }

        /* Farbkodierung für Ereignistypen */
        .eventType0 { background-color: #A7A6B1 }
        .eventType1 { background-color: #641A54; color: #ffffff }
        .eventType2 { background-color: #DA3F87 }
        .eventType3 { background-color: #EB7A3D }
        .eventType4 { background-color: #F1B640 }
        .eventType5 { background-color: #86C554 }
        .eventType6 { background-color: #2EAA63 }
        .eventType7 { background-color: #3DC3CB }
        .eventType8 { background-color: #168FC3 }
        .eventType9 { background-color: #4D908E }
        .eventType10 { background-color: #21485C }

        /* Stil für Wochenenden */
        .weekend {
            background-color: #ccc;
        }
    </style>
</head>
<body>
<div>
    <h1>{{ $title }}</h1>

    @php
        // Filter-Arrays defensiv casten
        $roomsFilter          = (array) ($user_filters->rooms ?? []);
        $areasFilter          = (array) ($user_filters->areas ?? []);
        $eventTypesFilter     = (array) ($user_filters->event_types ?? []);
        $roomCategoriesFilter = (array) ($user_filters->room_categories ?? []);
        $roomAttrsFilter      = (array) ($user_filters->room_attribute ?? []);

        $isLoud               = !empty($user_filters->is_loud);
        $isNotLoud            = !empty($user_filters->is_not_loud);
        $adjoinNoAudience     = !empty($user_filters->adjoining_no_audience);
        $adjoinNotLoud        = !empty($user_filters->adjoining_not_loud);
        $hasAudience          = !empty($user_filters->has_audience);
        $hasNoAudience        = !empty($user_filters->has_no_audience);
    @endphp

        <!-- Aktive Filter -->
    <div style="margin-bottom: 10px">
        <h6 style="margin-bottom: 2px">Aktive Filter:</h6>

        {{-- Räume --}}
        @foreach($filterRooms as $room)
            @if(in_array($room->id, $roomsFilter, true))
                <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $room->name }}
                </span>
            @endif
        @endforeach

        {{-- Bereiche --}}
        @foreach($events->areas as $area)
            @if(in_array($area['id'], $areasFilter, true))
                <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $area['name'] }}
                </span>
            @endif
        @endforeach

        {{-- Ereignistypen --}}
        @foreach($events->eventTypes as $eventType)
            @if(in_array($eventType['id'], $eventTypesFilter, true))
                <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $eventType['name'] }}
                </span>
            @endif
        @endforeach

        {{-- Raumkategorien --}}
        @foreach($events->roomCategories as $roomCategory)
            @if(in_array($roomCategory['id'], $roomCategoriesFilter, true))
                <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $roomCategory['name'] }}
                </span>
            @endif
        @endforeach

        {{-- Raumattribute --}}
        @foreach($events->roomAttributes as $room_attribute)
            @if(in_array($room_attribute['id'], $roomAttrsFilter, true))
                <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $room_attribute['name'] }}
                </span>
            @endif
        @endforeach

        {{-- Booleans --}}
        @if($isLoud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Laute Termine</span>
        @endif
        @if($isNotLoud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Ohne laute Termine</span>
        @endif
        @if($adjoinNoAudience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Ohne Nebenveranstaltung mit Publikum</span>
        @endif
        @if($adjoinNotLoud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Ohne laute Nebenveranstaltung</span>
        @endif
        @if($hasAudience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Mit Publikum</span>
        @endif
        @if($hasNoAudience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">Ohne Publikum</span>
        @endif
    </div>

    <table>
        <thead>
        <tr style="font-size: 10px">
            <th></th>
            @foreach($rooms as $room)
                <th style="padding: 2px">{{ $room->name }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($days as $day)
            {{-- Extra Zeile: Wochen-Trenner (KW) --}}
            @if(!empty($day['isExtraRow']))
                <tr>
                    <td colspan="{{ 1 + $rooms->count() }}" style="background:#f3f4f6; font-size:9px; padding:2px;">
                        KW: {{ $day['weekNumber'] ?? '' }}
                    </td>
                </tr>
                @continue
            @endif

            {{-- Normale Tageszeile --}}
            @php
                $isWeekend = !empty($day['isWeekend']);
                $fullDay   = $day['fullDay']   ?? '';
                $dayString = $day['dayString'] ?? '';
                $weekNum   = $day['weekNumber'] ?? null;
                $isMonday  = !empty($day['isMonday']);
            @endphp
            <tr>
                <th class="{{ $isWeekend ? 'weekend' : '' }}" style="text-align: right; padding: 2px; margin-right: 10px; width: 5%">
                    <div style="text-align: end">
                        {{ $dayString }} {{ $fullDay }}
                    </div>
                    @if($isMonday && $weekNum)
                        <div style="font-weight: lighter; text-align: end; font-size: 7px">
                            (KW: {{ $weekNum }})
                        </div>
                    @endif
                </th>

                @foreach($rooms as $room)
                    <td class="{{ $isWeekend ? 'weekend' : '' }}">
                        @foreach($calendar as $calendarEvent)
                            @php
                                // Events des Tages sicher holen
                                $eventsOfDay = $calendarEvent[$fullDay]['events'] ?? [];
                            @endphp
                            @foreach($eventsOfDay as $event)
                                @if(is_array($event) && isset($event['roomId']) && $room->id === $event['roomId'])
                                    @php
                                        // Klasse aus eventTypeId wenn vorhanden, sonst Inline-Fallback
                                        $eventTypeId = $event['eventTypeId'] ?? null;
                                        $bgColor     = $event['eventTypeColorBackground'] ?? null;
                                        $allDay      = !empty($event['allDay']);
                                        $abbr        = $event['eventTypeAbbreviation'] ?? '';
                                        $name        = $event['eventName'] ?? '';
                                        $startTime   = $event['startTime'] ?? null;
                                        $endTime     = $event['end'] ?? null;

                                        $classAttr   = $eventTypeId !== null ? 'eventType' . (int)$eventTypeId : '';
                                        $styleAttr   = $eventTypeId === null && $bgColor ? "background-color: {$bgColor}" : '';
                                    @endphp

                                    <div class="{{ $classAttr }}" style="font-size: 8px; border-bottom: 1px solid black; padding: 2px; {{ $styleAttr }}">
                                        <div>
                                            {{ $abbr ? $abbr . ': ' : '' }}{{ $name }}
                                        </div>
                                        <div>
                                            @if(!$allDay && $startTime && $endTime)
                                                {{ \Illuminate\Support\Carbon::parse($startTime)->timezone(config('app.timezone'))->format('H:i') }}
                                                -
                                                {{ \Illuminate\Support\Carbon::parse($endTime)->timezone(config('app.timezone'))->format('H:i') }}
                                            @else
                                                <span style="font-weight: bold">Ganztägig</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
</body>
</html>
