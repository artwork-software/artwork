<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Calendar\Services\ShiftPlanService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;

// Get the first room that has shifts
$roomId = \DB::table('shifts')
    ->whereNotNull('room_id')
    ->where('start_date', '>=', '2026-05-19')
    ->value('room_id');

echo "Testing room_id: $roomId\n\n";

$room = Room::find($roomId);
$filter = UserFilter::first();
$startDate = Carbon::parse('2026-05-19');
$endDate = Carbon::parse('2026-05-25');

$service = app(ShiftCalendarService::class);
$result = $service->filterRoomsEventsAndShifts(
    collect([$room]),
    $filter,
    $startDate,
    $endDate,
    true
);

$calendarData = $service->mapRoomsToContentForCalendar(
    $result['rooms'],
    $startDate,
    $endDate,
);

$roomDTO = $calendarData->rooms[0] ?? null;
if (!$roomDTO) {
    echo "No room data\n";
    exit;
}

echo "=== shiftsById sample ===\n";
$shiftsById = $roomDTO->shiftsById ?? [];
$count = 0;
foreach ($shiftsById as $id => $shiftDto) {
    if ($count >= 3) break;
    echo "Shift $id:\n";
    echo "  start (property): " . var_export($shiftDto->start, true) . "\n";
    echo "  end (property): " . var_export($shiftDto->end, true) . "\n";
    echo "  startDate (property): " . var_export($shiftDto->startDate, true) . "\n";
    $count++;
}

echo "\n=== JSON encode simulation ===\n";
$jsonResponse = json_encode(['room' => $roomDTO]);
$decoded = json_decode($jsonResponse, true);
$firstShift = reset($decoded['room']['shiftsById']);
if ($firstShift) {
    echo "First shift from JSON:\n";
    echo "  start: " . $firstShift['start'] . "\n";
    echo "  end: " . $firstShift['end'] . "\n";
    echo "  startDate: " . $firstShift['startDate'] . "\n";
}
