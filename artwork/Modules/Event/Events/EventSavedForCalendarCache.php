<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class EventSavedForCalendarCache
{
    use Dispatchable;

    public function __construct(public Event $event, public User|null $user = null) {}
}
