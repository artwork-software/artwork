<?php

namespace Tests\Feature\Console;

use Artwork\Core\Api\Models\ApiLog;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ArtworkPruneCommandTest extends FeatureTestCase
{
    #[Test]
    public function model_and_except_cannot_be_combined(): void
    {
        // Die Prüfung stand hinter dem early return für --model und griff deshalb nie
        $this->expectException(InvalidArgumentException::class);

        $this->artisan('model:prune', [
            '--model' => [ApiLog::class],
            '--except' => [WebhookDelivery::class],
        ])->run();
    }

    #[Test]
    public function explicitly_listed_models_are_still_pruned(): void
    {
        $this->artisan('model:prune', ['--model' => [ApiLog::class], '--pretend' => true])
            ->assertSuccessful();
    }
}
