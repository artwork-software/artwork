<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class RemoveExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $signature = 'app:remove-expired-invitations';

    /**
     * The console command description.
     *
     * @var string
     */
    //phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $description = 'Remove expired invitations from the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // get Invitations that are older than 14 days
        $invitations = Invitation::where('created_at', '<', now()->subDays(14))->get();
        $invitations->each->delete();

        return Command::SUCCESS;
    }
}
