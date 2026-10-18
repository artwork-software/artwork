<?php

namespace Artwork\Modules\Shift\Support;

/**
 * Request-/Job-bezogener Kurzschluss für SafeBroadcast: als scoped im Container registriert
 * (ShiftChangeServiceProvider), damit Octane/Swoole und Queue-Worker ihn je Request bzw. Job neu anlegen.
 */
final class SafeBroadcastCircuit
{
    private bool $open = false;

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function open(): void
    {
        $this->open = true;
    }
}
