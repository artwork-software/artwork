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
        // Festschreibungs-Workflow an: die KW-Anfragen (angefragt/bestätigt) sind Teil der Demo
        $general->shift_commit_workflow_enabled = true;
        $general->save();

        $shiftSettings = app(ShiftSettings::class);
        $shiftSettings->shift_confirmation_enabled = true;
        $shiftSettings->allow_shift_overbooking = true;
        $shiftSettings->save();

        $this->command?->info(
            'Settings: Mehrfacheinsatz-Warnung, Schichtbestätigung und Überbuchung für die Demo aktiviert.'
        );
    }

    // Farbwelt "artwork core" (Design-Basis-Moodboard): Ink #27233C, Papier #EDECE8,
    // Markenorange #EB7A3D (als Text auf hell: #B3541A), Nacht #151320, Neutral-40 #A7A6B1.
    private function bigLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 72" width="260" height="72">
  <rect x="0" y="8" width="56" height="56" rx="14" fill="#27233C"/>
  <text x="28" y="42" text-anchor="middle" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="24" font-weight="600" fill="#FCFCFB">aT</text>
  <path d="M14 52 Q28 42 42 52" stroke="#EB7A3D" stroke-width="3" fill="none" stroke-linecap="round"/>
  <text x="68" y="36" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif" font-size="24"
        font-weight="600" fill="#27233C">artwork</text>
  <text x="68" y="58" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif" font-size="14"
        font-weight="600" letter-spacing="3" fill="#B3541A">TESTHAUS</text>
  <rect x="196" y="14" width="58" height="20" rx="10" fill="#B3541A"/>
  <text x="225" y="28" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="12" font-weight="700" fill="#FFFFFF">DEMO</text>
</svg>
SVG;
    }

    private function smallLogoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <rect x="0" y="0" width="64" height="64" rx="14" fill="#27233C"/>
  <text x="32" y="36" text-anchor="middle" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="24" font-weight="600" fill="#FCFCFB">aT</text>
  <path d="M15 45 Q32 34 49 45" stroke="#EB7A3D" stroke-width="3.5" fill="none" stroke-linecap="round"/>
  <rect x="9" y="49" width="46" height="12" rx="6" fill="#EB7A3D"/>
  <text x="32" y="58" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="8.5" font-weight="700" letter-spacing="1.5" fill="#27233C">DEMO</text>
</svg>
SVG;
    }

    // Hochformat mit zentriertem Inhalt: der Login zeigt das Banner per object-cover
    // auf einer vollen Bildschirmhaelfte, alles ausserhalb der Mitte wird beschnitten.
    private function bannerSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 1200" width="900" height="1200">
  <rect width="900" height="1200" fill="#27233C"/>
  <circle cx="120" cy="1160" r="380" fill="#151320"/>
  <circle cx="820" cy="40" r="260" fill="#151320"/>
  <path d="M-80 320 Q450 80 980 320" stroke="#EB7A3D" stroke-width="4" fill="none"/>
  <path d="M-80 370 Q450 130 980 370" stroke="#A7A6B1" stroke-width="2" fill="none" opacity="0.3"/>
  <rect x="390" y="420" width="120" height="120" rx="28" fill="#EDECE8"/>
  <text x="450" y="492" text-anchor="middle" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="46" font-weight="600" fill="#27233C">aT</text>
  <path d="M418 518 Q450 496 482 518" stroke="#EB7A3D" stroke-width="6" fill="none" stroke-linecap="round"/>
  <text x="450" y="640" text-anchor="middle" font-family="Lexend, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="64" fill="#F4F4F2"><tspan font-weight="600">artwork</tspan><tspan font-weight="300"> Testhaus</tspan></text>
  <text x="450" y="690" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="22" fill="#A7A6B1">Demo-Umgebung – alle Daten sind fiktiv</text>
  <rect x="402" y="726" width="96" height="34" rx="17" fill="#EB7A3D"/>
  <text x="450" y="749" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="15" font-weight="700" letter-spacing="2" fill="#27233C">DEMO</text>
  <text x="450" y="840" text-anchor="middle" font-family="Inter, 'Helvetica Neue', Helvetica, Arial, sans-serif"
        font-size="16" fill="#A7A6B1" opacity="0.8">powered by artwork</text>
</svg>
SVG;
    }
}
