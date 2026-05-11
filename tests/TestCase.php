<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Concerns\ActsAsRole;
use Tests\Concerns\SilencesCaches;

abstract class TestCase extends BaseTestCase
{
    use ActsAsRole;
    use CreateAdminUser;
    use CreatesApplication;
    use DatabaseTransactions;
    use SilencesCaches;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'null']);

        $this->silenceCaches();
        $this->withoutVite();

        \Illuminate\Support\Facades\App::setLocale('en');
        \Illuminate\Support\Facades\Session::put('locale', 'en');
        \Carbon\CarbonPeriod::setLocale('en');
    }
}
