<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Repositories\MoneySourceReminderRepository;

readonly class MoneySourceReminderService
{
    public function __construct(private MoneySourceReminderRepository $moneySourceReminderRepository)
    {
    }
}
