<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Invitation\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Löscht Einladungen, deren Gültigkeit seit mehr als GRACE_DAYS_AFTER_EXPIRY Tagen abgelaufen ist.
 * Maßgeblich ist expires_at, nicht das Erstellungsdatum: Eine erneut verschickte Einladung verlängert
 * die Gültigkeit und bleibt deshalb entsprechend länger bestehen. Die Nachfrist entspricht dem
 * bisherigen Verhalten (Löschung 14 Tage nach Erstellung bei 7 Tagen Gültigkeit).
 */
class RemoveExpiredInvitationsCommand extends Command
{
    public const GRACE_DAYS_AFTER_EXPIRY = 7;

    /** Einladungen ohne Ablaufdatum (gelten als abgelaufen) werden wie bisher nach dem Alter gelöscht. */
    public const MAX_AGE_DAYS_WITHOUT_EXPIRY = 14;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $signature = 'artwork:remove-expired-invitations';

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
        $invitations = Invitation::query()
            ->where('expires_at', '<', now()->subDays(self::GRACE_DAYS_AFTER_EXPIRY))
            ->orWhere(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->where('created_at', '<', now()->subDays(self::MAX_AGE_DAYS_WITHOUT_EXPIRY));
            })
            ->get();
        $invitations->each->delete();

        return Command::SUCCESS;
    }
}
