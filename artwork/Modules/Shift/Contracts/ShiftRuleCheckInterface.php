<?php

namespace Artwork\Modules\Shift\Contracts;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ShiftRuleCheckInterface
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection;
    
    public function getTriggerType(): string;
}