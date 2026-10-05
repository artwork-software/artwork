<?php

namespace Tests\Feature\Http\Controllers\System;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class FileSettingsControllerTest extends FeatureTestCase
{
    /**
     * @return array{data: array{name: string, fileSize: mixed, fileTypes: array<int, array{name: string}>}}
     */
    private function payload(mixed $fileSize = 50, string $name = 'project'): array
    {
        return ['data' => ['name' => $name, 'fileSize' => $fileSize, 'fileTypes' => [['name' => 'png']]]];
    }

    #[Test]
    public function admin_can_update_file_size_and_types(): void
    {
        $this->actingAsAdmin();

        $this->put(route('tool.file-settings.store'), $this->payload(250))
            ->assertRedirect();

        $settings = app(GeneralSettings::class)->refresh();
        $this->assertSame(250, $settings->allowed_project_file_size);
        $this->assertSame(['png'], $settings->allowed_project_file_mimetypes);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidFileSizes(): array
    {
        return [
            'zero' => [0],
            'above max' => [1025],
            'not numeric' => ['abc'],
            'missing' => [null],
        ];
    }

    #[Test]
    #[DataProvider('invalidFileSizes')]
    public function invalid_file_size_is_rejected(mixed $fileSize): void
    {
        $this->actingAsAdmin();
        $before = app(GeneralSettings::class)->allowed_project_file_size;

        $this->put(route('tool.file-settings.store'), $this->payload($fileSize))
            ->assertSessionHasErrors('data.fileSize');

        $this->assertSame($before, app(GeneralSettings::class)->refresh()->allowed_project_file_size);
    }

    #[Test]
    public function unknown_area_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->put(route('tool.file-settings.store'), $this->payload(50, 'unknown'))
            ->assertSessionHasErrors('data.name');
    }

    #[Test]
    public function user_without_tool_settings_permission_cannot_update(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('tool.file-settings.store'), $this->payload())
            ->assertForbidden();
    }
}
