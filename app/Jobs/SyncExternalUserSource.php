<?php

namespace App\Jobs;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncExternalUserSource implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const int LOCK_SECONDS = 7200;

    public int $timeout = 3600;

    public int $tries = 3;

    public int $uniqueFor = self::LOCK_SECONDS;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public bool $failOnTimeout = true;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public ExternalUserSource $source)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->source->getKey();
    }

    public static function statusKey(int $sourceId): string
    {
        return 'external-user-sync-status-' . $sourceId;
    }

    public function handle(ExternalUserSyncService $syncService): void
    {
        $source = $this->source->fresh();

        if (!$source || $source->type !== 'ldap' || !$source->active) {
            $this->storeStatus('cancelled', __('LDAP synchronization was cancelled because the source is no longer active.'));

            return;
        }

        $lock = Cache::lock('external-user-sync-' . $this->source->id, self::LOCK_SECONDS);

        if (!$lock->get()) {
            $this->storeStatus('waiting', __('Another sync is still running. The queued sync will retry.'));
            $this->release(60);

            return;
        }

        $this->storeStatus('running', __('LDAP synchronization is running.'));

        try {
            $result = $syncService->syncSource($source);
            $this->storeStatus('completed', __('LDAP synchronization completed.'), $result);
        } catch (Throwable $exception) {
            $this->storeStatus('retrying', __('LDAP synchronization failed and will be retried.'));
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->storeStatus('failed', __('LDAP synchronization failed. Please check the logs.'));
    }

    /** @param array{total:int, synced:int, skipped:int}|null $result */
    private function storeStatus(string $status, string $message, ?array $result = null): void
    {
        Cache::put(self::statusKey($this->source->id), [
            'status' => $status,
            'message' => $message,
            'result' => $result,
            'updated_at' => now()->toIso8601String(),
        ], now()->addDay());
    }
}
