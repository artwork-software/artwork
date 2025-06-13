<?php

namespace Artwork\Modules\Event\Services;

use App\Settings\EventSettings;
use Psr\Log\LoggerInterface;
use Throwable;

class EventSettingsService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventSettings $eventSettings
    ) {
    }

    public function get($setting, $default = null)
    {
        try {
            return $this->eventSettings->__get($setting);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Event setting "%s" not found for reason: %s',
                    $setting,
                    $t->getMessage(),
                )
            );

            return $default;
        }
    }
}
