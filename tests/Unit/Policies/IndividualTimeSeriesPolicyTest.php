<?php

namespace Tests\Unit\Policies;

use App\Policies\IndividualTimeSeriesPolicy;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * NOTE: This policy class imports `App\IndividualTimeSeries`, which does not exist.
 * The actual model lives at `Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries`.
 * The methods that take this parameter cannot be invoked without the missing class.
 * Methods that only take a User are exercised normally.
 */
final class IndividualTimeSeriesPolicyTest extends TestCase
{
    private IndividualTimeSeriesPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(IndividualTimeSeriesPolicy::class);
    }

    #[Test]
    public function admin_cannot_view_any_due_to_locked_policy(): void
    {
        $this->assertFalse($this->policy->viewAny($this->adminUser()));
    }

    #[Test]
    public function user_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny(User::factory()->create()));
    }

    #[Test]
    public function user_cannot_create(): void
    {
        $this->assertFalse($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function policy_imports_missing_class_app_individual_time_series(): void
    {
        // Documented bug: IndividualTimeSeriesPolicy imports App\IndividualTimeSeries
        // but no such class exists. The actual model is
        // Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries.
        $this->assertFalse(class_exists(\App\IndividualTimeSeries::class));
        $this->assertTrue(class_exists(\Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries::class));
    }
}
