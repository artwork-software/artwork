{{-- KI-Export als Tabelle (core-ai-event-stats-exports). Inhalte kommen aus artwork-ai, deshalb nur escaped ausgeben. --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: "Helvetica Neue", Arial, sans-serif; font-size: 10pt; color: #1f2937; margin: 0; }
        h1 { font-size: 16pt; margin: 0 0 4px 0; }
        .subtitle { font-size: 10pt; color: #4b5563; margin: 0 0 12px 0; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #f3f4f6; text-align: left; font-weight: 600; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
        td.number { text-align: right; white-space: nowrap; }
        .note { margin-top: 12px; color: #4b5563; }
        .meta { margin-top: 16px; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if ($subtitle)
        <p class="subtitle">{{ $subtitle }}</p>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td @class(['number' => is_numeric($cell)])>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">Keine Eintraege.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($note)
        <p class="note">{{ $note }}</p>
    @endif

    <p class="meta">Erstellt mit dem KI-Assistenten von artwork am {{ $createdAt }}@if ($createdBy) von {{ $createdBy }}@endif · {{ count($rows) }} Zeilen</p>
</body>
</html>
