<?php

namespace Artwork\Modules\Crm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CrmSettingsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function broadcastAs(): string
    {
        return 'crm.settings.changed';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('crm.settings');
    }
}
