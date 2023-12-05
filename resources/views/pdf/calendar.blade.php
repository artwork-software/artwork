<!doctype html>
<html lang="en">
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
    <!-- Kalender -->
    <div style="margin-bottom: 10px">
        <h6 style="margin-bottom: 2px">Aktive Filter:</h6>
        @foreach($filterRooms as $room)
            @if(is_array($user_filters->rooms))
                @if(in_array($room->id, $user_filters->rooms))
                    <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                        {{ $room->name }}
                    </span>
                @endif
            @endif
        @endforeach
        @foreach($events->areas as $area)
            @if(is_array($user_filters->areas))
                @if(in_array($area['id'], $user_filters->areas))
                    <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                        {{ $area['name'] }}
                    </span>
                @endif
            @endif
        @endforeach
        @foreach($events->eventTypes as $eventType)
            @if(is_array($user_filters->event_types))
                @if(in_array($eventType['id'], $user_filters->event_types))
                    <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                    {{ $eventType['name'] }}
                </span>
                @endif
            @endif
        @endforeach
        @foreach($events->roomCategories as $roomCategory)
            @if(is_array($user_filters->room_categories))
                @if(in_array($roomCategory['id'], $user_filters->room_categories))
                    <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                        {{ $roomCategory['name'] }}
                    </span>
                @endif
            @endif
        @endforeach
        @foreach($events->roomAttributes as $room_attribute)
            @if(is_array($user_filters->room_attribute))
                @if(in_array($room_attribute['id'], $user_filters->room_attribute))
                    <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                        {{ $room_attribute['name'] }}
                    </span>
                @endif
            @endif
        @endforeach

        @if($user_filters->is_loud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Laute Termine
            </span>
        @endif
        @if($user_filters->is_not_loud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Ohne laute Termine
            </span>
        @endif
        @if($user_filters->adjoining_no_audience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Ohne Nebenveranstaltung mit Publikum
            </span>
        @endif
        @if($user_filters->adjoining_not_loud)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Ohne laute Nebenveranstaltun
            </span>
        @endif
        @if($user_filters->has_audience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Mit Publikum
            </span>
        @endif
        @if($user_filters->has_no_audience)
            <span style="border-radius: 100%; border: 1px solid #3017ad4d; background-color: #3017AD19; padding: 4px; font-size: 8px; margin: 3px">
                Ohne Publikum
            </span>
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
            <tr>
                <th class="{{ $day['is_weekend'] ? 'weekend' : '' }}" style="text-align: right; padding: 2px; margin-right: 10px; width: 5%">
                    <div style="text-align: end">
                        {{ $day['day_string'] }} {{ $day['full_day'] }}
                    </div>
                    @if($day['is_monday'])
                        <div style="font-weight: lighter; text-align: end; font-size: 7px">
                            (KW: {{ $day['week_number'] }})
                        </div>
                    @endif
                </th>
                @foreach($rooms as $room)
                    <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">
                        @foreach($calendar as $calendarEvent)
                            @foreach($calendarEvent[$day['day']] as $event)
                                <div class="{{ $event->event_type->svg_name }}" style="font-size: 8px; border-bottom: 1px solid black; padding: 2px;">
                                    <div>
                                        {{ $event->eventName ?? $event->name }}
                                    </div>

                                    <div>
                                        @if(!$event->allDay)
                                            {{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($event->end_time)->format('H:i') }}
                                        @else
                                            <span style="font-weight: bold">Ganztägig</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
