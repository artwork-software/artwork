<?php

namespace Artwork\Modules\MoneySourceReminder\Services;

use Artwork\Modules\MoneySourceReminder\Repositories\MoneySourceReminderRepository;

readonly class MoneySourceReminderService
{
    public function __construct(private MoneySourceReminderRepository $moneySourceReminderRepository)
    {
    }
}
