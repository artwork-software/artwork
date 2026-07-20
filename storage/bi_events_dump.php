<?php
$projects = \Artwork\Modules\Project\Models\Project::whereHas('events')->get();
$out = [];
foreach ($projects as $p) {
    $events = $p->events()->orderBy('start_time')->get()
        ->map(fn($e) => ['id' => $e->id, 'start_time' => $e->start_time?->format('d.m.Y H:i')])->values();
    $out[] = ['project' => $p->id, 'name' => $p->name, 'events' => $events];
}
file_put_contents('storage/bi_events.json', json_encode($out));
echo "written: " . count($out) . " projects\n";
