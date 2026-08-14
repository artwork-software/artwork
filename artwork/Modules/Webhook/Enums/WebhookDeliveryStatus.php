<?php

namespace Artwork\Modules\Webhook\Enums;

enum WebhookDeliveryStatus: string
{
    /** Angelegt, noch kein Zustellversuch abgeschlossen. */
    case PENDING = 'pending';

    /** Empfänger hat mit 2xx geantwortet. */
    case SUCCESS = 'success';

    /** Versuch fehlgeschlagen, ein weiterer ist eingeplant. */
    case FAILED = 'failed';

    /** Alle Versuche verbraucht, es wird nicht mehr zugestellt. */
    case EXHAUSTED = 'exhausted';

    public function isFinal(): bool
    {
        return $this === self::SUCCESS || $this === self::EXHAUSTED;
    }
}
