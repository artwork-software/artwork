<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    public function testBasicExample(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user());
            $browser->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }
}
