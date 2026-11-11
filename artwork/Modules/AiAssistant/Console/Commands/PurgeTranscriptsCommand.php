<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Illuminate\Console\Command;

/**
 * Loescht Gespraeche nach der Aufbewahrungsfrist des Hauses (core-chat-historie; Konzept C3 §8).
 *
 * 0 heisst unbegrenzt — dann passiert nichts. Geloescht wird hart und kaskadierend; die Turns
 * haengen per Fremdschluessel mit cascadeOnDelete daran.
 */
class PurgeTranscriptsCommand extends Command
{
    protected $signature = 'ai:purge-transcripts';

    protected $description = 'Loescht KI-Gespraeche, die aelter als die eingestellte Aufbewahrungsfrist sind.';

    public function handle(AiAssistantSettings $settings): int
    {
        $days = $settings->transcript_retention_days;

        if ($days <= 0) {
            $this->info('Aufbewahrung unbegrenzt (0) — nichts zu loeschen.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = 0;
        AiConversation::query()
            ->where('last_turn_at', '<', $cutoff)
            ->chunkById(200, function ($conversations) use (&$deleted): void {
                foreach ($conversations as $conversation) {
                    $conversation->delete();
                    $deleted++;
                }
            });

        $this->info(sprintf('%d Gespraech(e) aelter als %d Tage geloescht.', $deleted, $days));

        return self::SUCCESS;
    }
}
