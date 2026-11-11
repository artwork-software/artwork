<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Artwork\Modules\AiAssistant\Services\AiActionService;
use Illuminate\Console\Command;

/**
 * Raeumt bestaetigungspflichtige Aktionen weg, deren Frist verstrichen ist
 * (core-confirm-actions; Konzept W2 §1).
 *
 * Ohne den Job bliebe ein Gespraech nach einem Abbruch der Bar dauerhaft auf
 * `awaiting_confirmation` stehen — der naechste Turn liefe dann nie an.
 */
class ExpireActionsCommand extends Command
{
    protected $signature = 'ai:expire-actions';

    protected $description = 'Setzt abgelaufene KI-Aktionen auf "expired" und gibt ihr Gespraech frei.';

    public function handle(AiActionService $actions): int
    {
        $expired = $actions->expireDue();

        $this->info(sprintf('%d abgelaufene Aktion(en) aufgeraeumt.', $expired));

        return self::SUCCESS;
    }
}
