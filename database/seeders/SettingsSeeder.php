<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Storage::put(
            '/public/banner/default_banner.svg',
            File::get(public_path('/Svgs/defaultSvgs/default_banner.svg')),
            'public'
        );

        Storage::put(
            '/public/logo/artwork_logo_big.svg',
            File::get(public_path('/Svgs/Logos/artwork_logo_big.svg')),
            'public'
        );

        Storage::put(
            '/public/logo/artwork_logo_small.svg',
            File::get(public_path('/Svgs/Logos/artwork_logo_small.svg')),
            'public'
        );
    }
}
