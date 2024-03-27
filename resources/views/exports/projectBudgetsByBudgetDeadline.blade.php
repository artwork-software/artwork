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
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{$row['premiere']}}</td>
            <td>{{$row['project_name']}}</td>
            <td>{{$row['artist_or_group']}}</td>
            <td>{{$row['costCenter']}}</td>
            <td>{{$row['project_state']}}</td>
            <td>{{$row['forecast_costs']}}</td>
            <td>{{$row['forecast_earnings']}}</td>
            <td>{{$row['forecast_outcome']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
