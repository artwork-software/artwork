<?php

namespace Tests\Feature\Modules\Trash;

use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Papierkorb der Projekteinstellungen: Die Seite baut die Routennamen aus dem Modellnamen zusammen
 * ("genres.restore", "state.force", ...). Fehlt ein Name, passiert beim Klick nichts.
 */
final class TrashProjectSettingsRoutesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function trashModels(): array
    {
        return [
            'genres' => ['genres'],
            'categories' => ['categories'],
            'sectors' => ['sectors'],
            'state' => ['state'],
            'contract_types' => ['contract_types'],
            'company_types' => ['company_types'],
            'currencies' => ['currencies'],
        ];
    }

    #[Test]
    #[DataProvider('trashModels')]
    public function restore_and_force_routes_exist_and_take_exactly_one_parameter(string $model): void
    {
        foreach (['restore', 'force'] as $action) {
            $route = Route::getRoutes()->getByName($model . '.' . $action);

            $this->assertNotNull($route, "Route {$model}.{$action} fehlt");
            $this->assertCount(1, $route->parameterNames());
        }
    }

    #[Test]
    public function a_trashed_genre_is_restored_through_the_named_route(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
        $genre = Genre::create(['name' => 'Papierkorbprobe']);
        $genre->delete();

        $this->patch(route('genres.restore', $genre->id))->assertRedirect(route('projects.settings.trashed'));

        $this->assertNotNull(Genre::find($genre->id));
    }
}
