<?php

namespace Artwork\Core\Console\Commands;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\Passport;
use Throwable;

/**
 * Räumt widerrufene und abgelaufene OAuth-Tokens auf wie `passport:purge`, lässt aber die per
 * PASSPORT_EXTENDED_TOKENS verlängerten Tokens (config passport.extended_tokens, siehe ExtendedTokenValidator)
 * stehen, solange ihre Verlängerung samt Aufbewahrungsfrist läuft. Eine fehlende Zeile gilt beim Prüfen als
 * widerrufen; ohne diese Ausnahme wären verlängerte Integrationen nach dem ersten nächtlichen Lauf tot.
 * Widerrufene verlängerte Tokens werden wie alle anderen gelöscht (bleiben dadurch gesperrt).
 */
class PurgeOauthTokensCommand extends Command
{
    protected $signature = 'artwork:purge-oauth-tokens
                            {--hours=168 : Wie viele Stunden abgelaufene Tokens aufbewahrt werden}';

    protected $description = 'Purge revoked and expired OAuth tokens, keeping extended tokens until their deadline';

    public function handle(): int
    {
        $expiredBefore = CarbonImmutable::now()->subHours(max(0, (int) $this->option('hours')));
        $keptTokenIds = $this->runningExtendedTokenIds($expiredBefore);

        $constraint = static fn (Builder $query): Builder => $query
            ->where('revoked', true)
            ->orWhere('expires_at', '<', $expiredBefore);

        $deletedTokens = Passport::token()->newQuery()
            ->where($constraint)
            ->when(
                $keptTokenIds !== [],
                static fn (Builder $query): Builder => $query->where(
                    static fn (Builder $query): Builder => $query
                        ->where('revoked', true)
                        ->orWhereNotIn('id', $keptTokenIds)
                )
            )
            ->delete();

        Passport::authCode()->newQuery()->where($constraint)->delete();
        Passport::refreshToken()->newQuery()->where($constraint)->delete();

        if (Passport::$deviceCodeGrantEnabled) {
            Passport::deviceCode()->newQuery()->where($constraint)->delete();
        }

        $this->components->info(sprintf(
            'Purged %d tokens; kept %d extended tokens.',
            $deletedTokens,
            count($keptTokenIds)
        ));

        return self::SUCCESS;
    }

    /**
     * jtis (= oauth_access_tokens.id) verlängerter Tokens, deren Deadline nach der Aufbewahrungsgrenze liegt.
     * Eine nicht lesbare Deadline zählt als laufend: Löschen ließe sich nicht zurücknehmen.
     *
     * @return list<string>
     */
    private function runningExtendedTokenIds(CarbonInterface $expiredBefore): array
    {
        $kept = [];

        foreach ((array) config('passport.extended_tokens', []) as $jti => $deadline) {
            try {
                $isRunning = CarbonImmutable::parse((string) $deadline)->greaterThan($expiredBefore);
            } catch (Throwable) {
                $isRunning = true;
            }

            if ($isRunning) {
                $kept[] = (string) $jti;
            }
        }

        return $kept;
    }
}
