<?php

namespace Artwork\Modules\Calendar\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Live-Update einer Tagesbemerkung: ein globaler Channel für alle Ansichten
 * (Kalender, Planungskalender, Dienstplan), Payload-Form identisch zur
 * Upsert-Response bzw. CalendarPeriodDTO.dayRemark.
 */
class DayRemarkUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param string $date Y-m-d
     * @param array<string, mixed>|null $dayRemark {text, updated_by, updated_at} | null (gelöscht)
     */
    public function __construct(
        public string $date,
        public ?array $dayRemark,
    ) {
    }

    public function broadcastAs(): string
    {
        return 'day-remark.updated';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('day-remarks');
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'date' => $this->date,
            'dayRemark' => $this->dayRemark,
        ];
    }
}
