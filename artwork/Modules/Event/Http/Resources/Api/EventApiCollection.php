<?php

namespace Artwork\Modules\Event\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Seitenweise Liste der Termine für die Maschinen-API, mit Laravels links/meta zum Blättern.
 */
class EventApiCollection extends ResourceCollection
{
    public $collects = EventApiResource::class;

    /**
     * Ohne meta.links: Die Liste der Seitenlinks ist für eine Benutzeroberfläche gedacht und bläht
     * die Antwort nur auf. Zum Blättern genügt links.next.
     *
     * @param array<string, mixed> $paginated
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        unset($default['meta']['links']);

        return $default;
    }
}
