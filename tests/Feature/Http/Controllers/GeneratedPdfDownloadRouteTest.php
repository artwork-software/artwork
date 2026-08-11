<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * The generated-PDF download routes take the filename straight from the URL and
 * interpolate it into a storage path, so the route constraint is what keeps the
 * parameter inside the pdf/ directory.
 */
final class GeneratedPdfDownloadRouteTest extends FeatureTestCase
{
    #[Test]
    #[DataProvider('traversalUris')]
    public function a_download_route_does_not_match_a_traversal_attempt(string $uri): void
    {
        $this->get($uri)->assertNotFound();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function traversalUris(): array
    {
        return [
            'calendar export, encoded traversal' => ['/calendar/export/pdf/..%2f..%2f.env/download'],
            'calendar export, plain name' => ['/calendar/export/pdf/.env/download'],
            'artist residency, encoded traversal' => ['/project/artist-residencies/export-pdf/download/..%2f..%2f.env'],
            'artist residency, plain name' => ['/project/artist-residencies/export-pdf/download/.env'],
        ];
    }

    #[Test]
    public function a_hash_shaped_filename_still_reaches_the_route(): void
    {
        // Not authenticated, so a matching route redirects to the login page -
        // which is exactly what proves the constraint let this one through.
        $this->get('/calendar/export/pdf/' . str_repeat('a', 32) . '.pdf/download')
            ->assertRedirect(route('login'));
    }
}
