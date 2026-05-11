<?php

namespace Tests\Unit\Modules\Genre\Services;

use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Genre\Services\GenreService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GenreServiceTest extends TestCase
{
    private GenreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GenreService::class);
    }

    #[Test]
    public function get_all_returns_all_genres(): void
    {
        Genre::factory()->count(4)->create();

        $result = $this->service->getAll();

        $this->assertCount(4, $result);
    }

    #[Test]
    public function create_new_genre_returns_unsaved_genre_instance(): void
    {
        $genre = $this->service->createNewGenre();

        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertFalse($genre->exists);
    }

    #[Test]
    public function create_persists_genre_with_name_and_color(): void
    {
        $payload = new Collection([
            'name' => 'Jazz',
            'color' => '#ff5500',
        ]);

        $genre = $this->service->create($payload);

        $this->assertTrue($genre->exists);
        $this->assertSame('Jazz', $genre->name);
        $this->assertSame('#ff5500', $genre->color);
        $this->assertDatabaseHas('genres', ['name' => 'Jazz', 'color' => '#ff5500']);
    }

    #[Test]
    public function create_ignores_extra_fields_in_request_collection(): void
    {
        $payload = new Collection([
            'name' => 'Classical',
            'color' => '#000000',
            'unknown' => 'value',
        ]);

        $genre = $this->service->create($payload);

        $this->assertSame('Classical', $genre->name);
        $this->assertSame('#000000', $genre->color);
    }
}
