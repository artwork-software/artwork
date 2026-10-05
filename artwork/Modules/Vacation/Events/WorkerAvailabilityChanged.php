<?php

namespace Artwork\Modules\Vacation\Events;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Verfügbarkeit/Abwesenheit einer Person wurde angelegt, geändert oder gelöscht.
 * Der Dienstplan lädt daraufhin die betroffene Personenzeile nach (useShiftCalendarListener),
 * damit Abwesenheits-Beschriftung und Konflikt-Markierungen ohne Reload aktuell sind.
 */
class WorkerAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int $workerId
     * @param int $workerType 0=User, 1=Freelancer, 2=ServiceProvider
     */
    public function __construct(
        public readonly int $workerId,
        public readonly int $workerType,
    ) {
    }

    /**
     * Aus morph-Typ (Klassenname der vacationer_type/available_type-Spalte) und ID.
     */
    public static function forMorph(string $morphClass, int $id): self
    {
        $type = match ($morphClass) {
            Freelancer::class => 1,
            ServiceProvider::class => 2,
            default => 0,
        };

        return new self($id, $type);
    }

    public function broadcastAs(): string
    {
        return 'worker-availability.changed';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shift-plan.worker-availability'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return [
            'workerId' => $this->workerId,
            'workerType' => $this->workerType,
        ];
    }
}
