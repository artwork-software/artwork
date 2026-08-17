<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Settings\ShiftSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Branding "Artwork Testhaus": Firmenname, Briefkopf, Demo-Logo/-Banner und
 * demofreundliche Schicht-Settings. Bewusst konservativ: individuell
 * hochgeladene Logos oder geänderte Namen werden nicht überschrieben.
 */
class DemoBrandingSeeder extends Seeder
{
    private const BIG_LOGO_PATH = 'logo/testhaus_logo_big.svg';
    private const SMALL_LOGO_PATH = 'logo/testhaus_logo_small.svg';
    private const BANNER_PATH = 'banner/testhaus_banner.svg';

    /** Werte, die als "noch nie angefasst" gelten und ersetzt werden dürfen. */
    private const DEFAULT_LOGO_PATHS = [
        '', 'logo/artwork_logo_big.svg', 'logo/artwork_logo_small.svg',
        'public/logo/artwork_logo_big.svg', 'public/logo/artwork_logo_small.svg',
    ];
    private const DEFAULT_BANNER_PATHS = ['', 'banner/default_banner.svg', 'public/banner/default_banner.svg'];
    private const DEFAULT_COMPANY_NAMES = ['', 'artwork', 'Artwork', 'artwork.software'];

    public function run(): void
    {
        $settings = app(GeneralSettings::class);

        if (in_array(trim($settings->company_name), self::DEFAULT_COMPANY_NAMES, true)) {
            $settings->company_name = DemoDataPools::COMPANY_NAME;
            $settings->page_title = DemoDataPools::COMPANY_NAME;
            $settings->business_name = DemoDataPools::COMPANY_NAME . ' gGmbH';
            $settings->impressum_link = 'https://artwork.software/impressum';
            $settings->privacy_link = 'https://artwork.software/datenschutz';
            $settings->letterhead_name = DemoDataPools::COMPANY_NAME . ' gGmbH';
            $settings->letterhead_street = 'Speicherstraße 12';
            $settings->letterhead_zip_code = '20457';
            $settings->letterhead_city = 'Hamburg';
            $settings->letterhead_email = 'demo@' . DemoDataPools::EMAIL_DOMAIN;
            $this->command?->info('Branding: Firmenname/Briefkopf auf "Artwork Testhaus" gesetzt.');
        } else {
            $this->command?->warn(
                sprintf('Branding: Firmenname "%s" bereits gepflegt – bleibt unangetastet.', $settings->company_name)
            );
        }

        $this->seedLogos($settings);
        $settings->save();

        $this->seedShiftSettings();
    }

    private function seedLogos(GeneralSettings $settings): void
    {
        $disk = Storage::disk('public');
        $disk->put(self::BIG_LOGO_PATH, $this->bigLogoSvg());
        $disk->put(self::SMALL_LOGO_PATH, $this->smallLogoSvg());
        $disk->put(self::BANNER_PATH, $this->bannerSvg());

        if (in_array($settings->big_logo_path, [...self::DEFAULT_LOGO_PATHS, self::BIG_LOGO_PATH], true)) {
            $settings->big_logo_path = self::BIG_LOGO_PATH;
            $settings->small_logo_path = self::SMALL_LOGO_PATH;
            $this->command?->info('Branding: Demo-Logos gesetzt.');
        } else {
            $this->command?->warn('Branding: eigenes Logo erkannt – Demo-Logo nur abgelegt, nicht aktiviert.');
        }

        if (in_array($settings->banner_path, [...self::DEFAULT_BANNER_PATHS, self::BANNER_PATH], true)) {
            $settings->banner_path = self::BANNER_PATH;
        }
    }

    private function seedShiftSettings(): void
    {
        $general = app(GeneralSettings::class);
        $general->warn_multiple_assignments = true;
        $general->save();

        $shiftSettings = app(ShiftSettings::class);
        $shiftSettings->shift_confirmation_enabled = true;
        $shiftSettings->allow_shift_overbooking = true;
        $shiftSettings->save();

        $this->command?->info(
            'Settings: Mehrfacheinsatz-Warnung, Schichtbestätigung und Überbuchung für die Demo aktiviert.'
        );
    }

    private function bigLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 72" width="260" height="72">
  <rect x="0" y="8" width="56" height="56" rx="14" fill="#9e1c60"/>
  <text x="28" y="46" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
        font-size="26" font-weight="700" fill="#ffffff">aT</text>
  <text x="68" y="36" font-family="Helvetica, Arial, sans-serif" font-size="24"
        font-weight="700" fill="#1f2937">artwork</text>
  <text x="68" y="58" font-family="Helvetica, Arial, sans-serif" font-size="15"
        font-weight="600" letter-spacing="3" fill="#9e1c60">TESTHAUS</text>
  <rect x="196" y="14" width="58" height="20" rx="10" fill="#f59e0b"/>
  <text x="225" y="28" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
        font-size="12" font-weight="700" fill="#ffffff">DEMO</text>
</svg>
SVG;
    }

    private function smallLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <rect x="0" y="0" width="64" height="64" rx="16" fill="#9e1c60"/>
  <text x="32" y="40" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
        font-size="26" font-weight="700" fill="#ffffff">aT</text>
  <rect x="8" y="46" width="48" height="13" rx="6" fill="#f59e0b"/>
  <text x="32" y="56" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
        font-size="9" font-weight="700" fill="#ffffff">DEMO</text>
</svg>
SVG;
    }

    private function bannerSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 240" width="1200" height="240">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#9e1c60"/>
      <stop offset="1" stop-color="#4c1d95"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="240" fill="url(#bg)"/>
  <text x="60" y="120" font-family="Helvetica, Arial, sans-serif" font-size="56"
        font-weight="700" fill="#ffffff">Artwork Testhaus</text>
  <text x="60" y="165" font-family="Helvetica, Arial, sans-serif" font-size="24"
        fill="#fbcfe8">Demo-Umgebung – alle Daten sind fiktiv</text>
  <rect x="60" y="185" width="96" height="30" rx="15" fill="#f59e0b"/>
  <text x="108" y="206" text-anchor="middle" font-family="Helvetica, Arial, sans-serif"
        font-size="16" font-weight="700" fill="#ffffff">DEMO</text>
</svg>
SVG;
    }
}
