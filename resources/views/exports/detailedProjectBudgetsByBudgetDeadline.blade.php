<table>
    <thead>
    <tr>
        <th>Premiere</th>
        <th>Projektname Originalsprache</th>
        <th>Künstler / Gruppe</th>
        <th>KTR</th>
        <th>Projektstatus</th>
        <th>Vorschau Kosten</th>
        <th>Vorschau Erlöse</th>
        <th>Vorschau Resultat</th>
        <th>Sage</th>
        <th>Sage Erlöse</th>
        <th>Sage Ergebnis</th>
        <th>Quelle (Name)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{$row['premiere']}}</td>
            <td>{{$row['project_name']}}</td>
            <td>{{$row['artist_or_group']}}</td>
            <td>{{$row['cost_center'] ?? '-'}}</td>
            <td>{{$row['project_state']}}</td>
            <td>{{$row['forecast_costs']}}</td>
            <td>{{$row['forecast_earnings']}}</td>
            <td>{{$row['forecast_outcome']}}</td>
            <td>{{$row['sage']}}</td>
            <td>{{$row['sage_revenue']}}</td>
            <td>{{$row['sage_result']}}</td>
            <td>{{$row['source']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
