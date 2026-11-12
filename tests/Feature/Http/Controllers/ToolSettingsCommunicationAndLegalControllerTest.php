<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ToolSettingsCommunicationAndLegalControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_communication_and_legal(): void
    {
        $this->get(route('tool.communication-and-legal'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_communication_and_legal(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tool.communication-and-legal'));

        $response->assertOk();
    }

    #[Test]
    public function legal_links_must_use_https_like_the_form_requires(): void
    {
        $this->actingAsAdmin();

        $generalSettings = app(GeneralSettings::class);
        $generalSettings->impressum_link = 'http://example.test/alt';
        $generalSettings->save();

        $this->patch(route('tool.communication-and-legal.update'), [
            'impressumLink' => 'http://example.test/impressum',
            'privacyLink' => 'http://example.test/datenschutz',
        ])->assertSessionHasErrors(['impressumLink', 'privacyLink']);

        $this->assertSame('http://example.test/alt', app(GeneralSettings::class)->refresh()->impressum_link);

        $this->patch(route('tool.communication-and-legal.update'), [
            'impressumLink' => 'https://example.test/impressum',
            'privacyLink' => 'https://example.test/datenschutz',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://example.test/impressum', app(GeneralSettings::class)->refresh()->impressum_link);
    }
}
