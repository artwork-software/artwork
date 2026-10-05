<?php

namespace Tests\Feature\ExternalAccess;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Tests\TestCase;

/**
 * Basis für alle Tests des Moduls: Das Feature ist instanzweit per Einstellung abschaltbar
 * (Standard: aus) und wird hier für die Dauer des Tests freigeschaltet. DatabaseTransactions
 * rollt die Einstellung nach jedem Test zurück.
 */
abstract class ExternalAccessTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableExternalAccess();
    }

    protected function enableExternalAccess(bool $enabled = true): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->enabled = $enabled;
        $settings->save();
    }
}
