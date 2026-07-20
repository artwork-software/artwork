<?php
$f = \Artwork\Modules\Freelancer\Models\Freelancer::query()->whereHas('shifts')->first();
if (!$f) { $f = \Artwork\Modules\Freelancer\Models\Freelancer::first(); echo "no freelancer with shifts, using first\n"; }
echo 'Freelancer #'.$f->id."\n";
$shift = $f->shifts()->orderByDesc('start_date')->first();
$start = $shift ? \Carbon\Carbon::parse($shift->start_date)->startOfWeek() : now()->startOfWeek();
$end = $start->copy()->addWeeks(2);
$svc = app(\Artwork\Modules\Event\Services\EventService::class);
$days = $svc->getDaysWithEventsAndTotalPlannedWorkingHours($f->id, 'freelancer', $start, $end);
$withShifts = collect($days)->filter(fn($d) => count($d['shifts']) > 0);
echo 'days: '.count($days).', days with shifts: '.$withShifts->count()."\n";
echo json_encode($withShifts->map(fn($d) => ['date' => $d['date'], 'shifts' => count($d['shifts']), 'work' => $d['totalWorkTime']])->values()->take(5))."\n";
