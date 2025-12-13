<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Leihschein</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1f2937; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 0.875rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .w-full { width: 100%; }
        .border { border: 1px solid #d1d5db; }
        .border-b { border-bottom: 1px solid #d1d5db; }
        .border-t { border-top: 1px solid #d1d5db; }
        .border-collapse { border-collapse: collapse; }
        .p-2 { padding: 0.5rem; }
        .p-4 { padding: 1rem; }
        .mt-4 { margin-top: 1rem; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .w-1-3 { width: 33.333333%; }
        .bg-gray-100 { background-color: #f3f4f6; }
        .line { border-bottom: 1px solid #9ca3af; margin-top: 1.5rem; height: 2px; }
        .label { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; text-align: center; }
    </style>
</head>
<body class="p-4">

<div class="text-center mb-6">
    <h2 class="font-bold text-xl">Leihschein Nr. {{ $issue->id }}</h2>
    <p class="text-sm">Ausgabe: {{ $issue->issue_date->format('d.m.Y') }} | Rückgabe: {{ $issue->return_date->format('d.m.Y') }}</p>
</div>

<div class="mb-4">
    <p class="font-bold">Externe Person/Firma:</p>
    <p>{{ $issue->external_name }}<br>
        {{ $issue->external_address }}<br>
        {{ $issue->external_email }} | {{ $issue->external_phone }}</p>
</div>

<div class="mb-4">
    @if($issue->issuedBy)
        <p><span class="font-bold">Ausgabe durch:</span> {{ $issue->issuedBy->full_name }}</p>
    @endif
    @if($issue->receivedBy)
        <p><span class="font-bold">Zurückgenommen durch:</span> {{ $issue->receivedBy->full_name }}</p>
    @endif
</div>

<div class="mb-6">
    <p><span class="font-bold">Materialwert:</span> {{ number_format($issue->material_value, 2, ',', '.') }} €</p>
</div>

<div class="mb-6">
    <p class="font-bold mb-2">Artikel:</p>
    <table class="w-full border border-collapse text-sm">
        <thead class="bg-gray-100">
        <tr>
            <th class="p-2 border-b text-left">Artikelname</th>
            <th class="p-2 border-b text-left">Kategorie</th>
            <th class="p-2 border-b text-left">Unterkategorie</th>
            <th class="p-2 border-b text-left">Menge</th>
        </tr>
        </thead>
        <tbody>
        @foreach($issue->articles as $article)
            <tr>
                <td class="p-2 border-b">{{ $article->name }}</td>
                <td class="p-2 border-b">{{ $article->category?->name }}</td>
                <td class="p-2 border-b">{{ $article->subCategory?->name }}</td>
                <td class="p-2 border-b">{{ $article->pivot->quantity }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if($issue->specialItems->count())
    <div class="mb-6">
        <p class="font-bold mb-2">Sonderartikel:</p>
        <table class="w-full border border-collapse text-sm">
            <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border-b text-left">Name</th>
                <th class="p-2 border-b text-left">Kategorie</th>
                <th class="p-2 border-b text-left">Unterkategorie</th>
                <th class="p-2 border-b text-left">Menge</th>
                <th class="p-2 border-b text-left">Beschreibung</th>
            </tr>
            </thead>
            <tbody>
            @foreach($issue->specialItems as $item)
                <tr>
                    <td class="p-2 border-b">{{ $item->name }}</td>
                    <td class="p-2 border-b">{{ $item->category?->name }}</td>
                    <td class="p-2 border-b">{{ $item->subCategory?->name }}</td>
                    <td class="p-2 border-b">{{ $item->quantity }}</td>
                    <td class="p-2 border-b">{{ $item->description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mb-6">
    <p class="font-bold mb-2">Mängel nach Rückgabe:</p>
    <p class="text-sm">{!! nl2br(e($issue->return_remarks)) !!}</p>
</div>

<div class="mt-4 text-sm">
    <table class="w-full text-center">
        <tr>
            <td class="w-1-3">
                <div class="line"></div>
                <div class="label">Ort / Datum</div>
            </td>
            <td class="w-1-3">
                <div class="line"></div>
                <div class="label">Unterschrift EmpfängerIn</div>
            </td>
            <td class="w-1-3">
                <div class="line"></div>
                <div class="label">Unterschrift zurücknehmend</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
