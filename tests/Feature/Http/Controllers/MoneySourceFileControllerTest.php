<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Illuminate\Http\UploadedFile;
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
}
