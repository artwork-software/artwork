<table>
    <thead>
    <tr>
        <th>Premiere</th>
        <th>Projektname Originalsprache</th>
        <th>Künstler / Gruppe</th>
        <th>Projektstatus</th>
        <th>KTR</th>
        <th>Kst</th>
        <th>Sachkonto</th>
        <th>Vorschau Kosten</th>
        <th>Vorschau Erlöse</th>
        <th>Vorschau Resultat</th>
        <th>Ist - Sage</th>
        <th>Ist Erlöse</th>
        <th>Ist Resultat</th>
        <th>Quelle (Name der Spalte im Budget)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{$row['premiere']}}</td>
            <td>{{$row['project_name']}}</td>
            <td>{{$row['artist_or_group']}}</td>
            <td>{{$row['project_state']}}</td>
            <td>{{$row['cost_center'] ?? '-'}}</td>
            <td>{{$row['kst']}}</td>
            <td>{{$row['real_account']}}</td>
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
