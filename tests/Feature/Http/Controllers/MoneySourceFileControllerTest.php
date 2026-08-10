<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceFileControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_money_source_file(): void
    {
        $source = MoneySource::factory()->create();

        $this->post(route('money_sources_files.store', ['money_source' => $source->id]), [
            'file' => UploadedFile::fake()->create('test.pdf', 100),
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_money_source_file(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(
            route('money_sources_files.store', ['money_source' => $source->id]),
            ['file' => UploadedFile::fake()->create('test.pdf', 100)]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('money_source_files', [
            'money_source_id' => $source->id,
            'name' => 'test.pdf',
        ]);
    }

    #[Test]
    public function stored_file_name_is_hashed_while_the_display_name_stays_raw(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $originalName = 'Beleg °^„2026“ 12:30.pdf';

        $this->post(
            route('money_sources_files.store', ['money_source' => $source->id]),
            ['file' => UploadedFile::fake()->create($originalName, 10, 'application/pdf')]
        )->assertRedirect();

        $file = MoneySourceFile::query()->firstOrFail();

        $this->assertSame($originalName, $file->name);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.[a-z0-9]+$/', $file->basename);
        Storage::assertExists('money_source_files/' . $file->basename);
    }
}
