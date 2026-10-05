<?php

namespace Tests\Feature\Settings;

use Artwork\Modules\GeneralSettings\Models\FormatSettings;
use Artwork\Modules\GeneralSettings\Services\InstanceFormatter;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regionale Formate der Instanz: Standard = bisheriges deutsches Format, umstellbar über
 * Einstellungen → Tool → Regionale Formate, als Prop "instanceFormat" im Frontend.
 */
final class InstanceFormatTest extends FeatureTestCase
{
    private function plain(string $text): string
    {
        return str_replace(["\u{a0}", "\u{202f}", "\u{2019}"], [' ', ' ', "'"], $text);
    }

    private function formatter(): InstanceFormatter
    {
        return app(InstanceFormatter::class);
    }

    #[Test]
    public function the_defaults_keep_the_former_german_format(): void
    {
        $this->assertSame('1.234,50', $this->formatter()->number(1234.5));
        $this->assertSame('1.234,50 €', $this->plain($this->formatter()->currency('1234.5')));
        $this->assertSame('31.12.2026', $this->formatter()->date('2026-12-31'));
        $this->assertSame('', $this->formatter()->date(null));
    }

    #[Test]
    public function a_swiss_instance_formats_with_apostrophes_and_francs(): void
    {
        $settings = app(FormatSettings::class);
        $settings->fill(['number_locale' => 'de-CH', 'currency' => 'CHF', 'date_format' => 'Y-m-d'])->save();

        $this->assertSame("1'234'567.89", $this->plain($this->formatter()->number(1234567.891)));
        $this->assertSame("CHF 1'234.50", $this->plain($this->formatter()->currency(1234.5)));
        $this->assertSame('2026-12-31', $this->formatter()->date('2026-12-31'));
    }

    #[Test]
    public function tool_admins_change_the_formats_and_the_frontend_receives_them(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('tool.formats'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('ToolSettings/Formats/Index')
                ->where('formatSettings.currency', 'EUR')
                ->where('instanceFormat.numberLocale', 'de-DE'));

        $this->patch(route('tool.formats.update'), [
            'number_locale' => 'en-GB',
            'currency' => 'GBP',
            'date_format' => 'd/m/Y',
        ])->assertRedirect();

        $this->get(route('tool.formats'))
            ->assertInertia(fn ($page) => $page->where('instanceFormat', [
                'numberLocale' => 'en-GB',
                'currency' => 'GBP',
                'dateFormat' => 'd/m/Y',
            ]));
    }

    #[Test]
    public function unknown_values_and_users_without_tool_rights_are_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $this->patch(route('tool.formats.update'), [
            'number_locale' => 'xx-XX',
            'currency' => 'BTC',
            'date_format' => 'Y',
        ])->assertSessionHasErrors(['number_locale', 'currency', 'date_format']);

        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $this->get(route('tool.formats'))->assertForbidden();
        $this->patch(route('tool.formats.update'), [
            'number_locale' => 'en-US',
            'currency' => 'USD',
            'date_format' => 'm/d/Y',
        ])->assertForbidden();
        $this->assertSame('EUR', app(FormatSettings::class)->currency);
    }
}
