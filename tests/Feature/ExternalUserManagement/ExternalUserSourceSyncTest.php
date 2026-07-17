<?php

namespace Tests\Feature\ExternalUserManagement;

use App\Jobs\SyncExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ExternalUserSourceSyncTest extends FeatureTestCase
{
    #[Test]
    public function manual_sync_is_queued_and_exposes_its_status(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $source = $this->source();

        $this->postJson(route('tool.external-user-management.sources.sync', $source))
            ->assertAccepted()
            ->assertJsonPath('success', true);
        Bus::assertDispatched(SyncExternalUserSource::class, fn (SyncExternalUserSource $job): bool =>
            $job->source->is($source));

        $this->getJson(route('tool.external-user-management.sources.sync-status', $source))
            ->assertOk()
            ->assertJsonPath('status', 'queued');
    }

    #[Test]
    public function manual_sync_requires_settings_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->postJson(route('tool.external-user-management.sources.sync', $this->source()))
            ->assertForbidden();
        Bus::assertNotDispatched(SyncExternalUserSource::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function source(): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test'],
        ]);
    }
}
