<?php

namespace Tests\Feature\Lang;

use Artwork\Core\Localization\SupportedLocales;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Sprachen kommen allein aus app.supported_locales: Eine neue Sprache braucht dort einen Eintrag
 * plus Übersetzungsdateien, aber keine Codeänderung.
 */
final class SupportedLocalesTest extends FeatureTestCase
{
    private function addDutch(): void
    {
        config(['app.supported_locales' => [
            'de' => 'Deutsch',
            'en' => 'English',
            'nl' => 'Nederlands',
        ]]);
    }

    #[Test]
    public function german_and_english_are_supported_with_their_own_names(): void
    {
        $this->assertSame(['de', 'en'], SupportedLocales::codes());
        $this->assertSame(
            [['code' => 'de', 'name' => 'Deutsch'], ['code' => 'en', 'name' => 'English']],
            SupportedLocales::toFrontend()
        );
        $this->assertTrue(SupportedLocales::isSupported('de'));
        $this->assertFalse(SupportedLocales::isSupported('nl'));
        $this->assertFalse(SupportedLocales::isSupported(null));
    }

    #[Test]
    public function the_older_list_format_is_still_understood(): void
    {
        config(['app.supported_locales' => ['de', 'en']]);

        $this->assertSame(['de', 'en'], SupportedLocales::codes());
        $this->assertSame(['de' => 'de', 'en' => 'en'], SupportedLocales::names());
    }

    #[Test]
    public function an_empty_configuration_falls_back_to_the_fallback_locale(): void
    {
        config(['app.supported_locales' => []]);

        $this->assertSame(['en'], SupportedLocales::codes());
    }

    #[Test]
    public function the_selectable_languages_are_shared_with_the_frontend(): void
    {
        $this->addDutch();
        $this->actingAsAdmin();

        $this->get(route('tool.branding'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('supportedLocales', [
                ['code' => 'de', 'name' => 'Deutsch'],
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'nl', 'name' => 'Nederlands'],
            ]));
    }

    #[Test]
    public function a_configured_language_becomes_the_users_interface_and_mail_language(): void
    {
        $this->addDutch();
        $user = $this->actingAsAdmin(User::factory()->create(['language' => 'nl']));

        $this->assertSame('nl', $user->preferredLocale());

        $this->get(route('tool.branding'))->assertOk();

        $this->assertSame('nl', App::getLocale());
    }

    #[Test]
    public function an_unconfigured_language_falls_back_to_the_app_locale(): void
    {
        $user = $this->actingAsAdmin(User::factory()->create(['language' => 'nl']));

        $this->assertNull($user->preferredLocale());

        $this->get(route('tool.branding'))->assertOk();

        $this->assertSame(config('app.locale'), App::getLocale());
    }

    #[Test]
    public function users_can_pick_a_configured_language_in_their_profile(): void
    {
        $this->addDutch();
        $user = $this->actingAsAdmin(User::factory()->create(['language' => 'de']));

        $this->patch(route('user.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'language' => 'nl',
        ])->assertSessionHasNoErrors();

        $this->assertSame('nl', $user->fresh()->language);
    }

    #[Test]
    public function an_unconfigured_language_is_rejected_in_the_profile(): void
    {
        $user = $this->actingAsAdmin(User::factory()->create(['language' => 'de']));

        $this->patch(route('user.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'language' => 'xx',
        ])->assertSessionHasErrors('language');

        $this->assertSame('de', $user->fresh()->language);
    }
}
