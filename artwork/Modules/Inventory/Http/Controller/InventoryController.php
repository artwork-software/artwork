<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Services\CraftService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class InventoryController extends Controller
{
    public function __construct(private readonly CraftService $craftService)
    {
    }

    public function inventory(): Response
    {
        throw new UnauthorizedHttpException('Unauthorized');
        return Inertia::render(
            'Inventory/Inventory',
            [
                'columns' => [
                    [
                        'id' => 1,
                        'type' => 'text',
                        'name' => 'Name'
                    ],
                    [
                        'id' => 2,
                        'type' => 'number',
                        'name' => 'Anzahl'
                    ],
                    [
                        'id' => 3,
                        'type' => 'textarea',
                        'name' => 'Kommentar'
                    ],
                    [
                        'id' => 4,
                        'type' => 'date',
                        'name' => 'Datum'
                    ],
                    [
                        'id' => 5,
                        'type' => 'checkbox',
                        'name' => 'Kürzlich aufbereitet'
                    ],
                    [
                        'id' => 6,
                        'type' => 'select',
                        'name' => 'Maximale Dispositionsdauer',
                        'options' => [
                            [
                                '1 Tag',
                                '3 Tage',
                                '1 Woche',
                                '2 Wochen',
                                '1 Monat'
                            ]
                        ]
                    ]
                ],
                'crafts' => $this->craftService->getAll()
            ]
        );
    }

    public function scheduling(): Response
    {
        throw new UnauthorizedHttpException('Unauthorized');
        return Inertia::render(
            'Inventory/Scheduling',
            [
                'crafts' => $this->craftService->getAll()
            ]
        );
    }
}
