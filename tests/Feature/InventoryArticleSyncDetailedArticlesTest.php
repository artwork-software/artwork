<?php

/**
 * Regression-Tests für den Match-and-Update-Flow von DetailArticles
 * (siehe InventoryArticleService::syncDetailedArticles).
 */

use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\TypeNumberGenerator;

beforeEach(function (): void {
    $this->actingAs($this->adminUser());

    $this->category = InventoryCategory::factory()->create();

    $this->status = InventoryArticleStatus::query()->create([
        'name' => 'Einsatzbereit',
        'color' => '#10b981',
        'order' => 1,
    ]);

    $this->property = InventoryArticleProperties::query()->create([
        'name' => 'Material',
        'type' => 'string',
    ]);

    $this->article = InventoryArticle::factory()->create([
        'name' => 'Stuhl',
        'inventory_category_id' => $this->category->id,
        'is_detailed_quantity' => true,
        'quantity' => 3,
    ]);

    // Both external_id and inventory_number are auto-generated via model boot
    $mainExternalId = $this->article->external_id;
    $mainInventoryNumber = $this->article->inventory_number;

    $this->detail1 = $this->article->detailedArticleQuantities()->create([
        'name' => 'Stuhl A',
        'description' => 'erster',
        'quantity' => 1,
        'inventory_article_status_id' => $this->status->id,
        'detail_number' => 1,
        'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 1),
        'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 1),
    ]);
    $this->detail2 = $this->article->detailedArticleQuantities()->create([
        'name' => 'Stuhl B',
        'description' => 'zweiter',
        'quantity' => 1,
        'inventory_article_status_id' => $this->status->id,
        'detail_number' => 2,
        'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 2),
        'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 2),
    ]);
    $this->detail3 = $this->article->detailedArticleQuantities()->create([
        'name' => 'Stuhl C',
        'description' => 'dritter',
        'quantity' => 1,
        'inventory_article_status_id' => $this->status->id,
        'detail_number' => 3,
        'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 3),
        'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 3),
    ]);
});

/**
 * Baut einen Eintrag für den `detailed_article_quantities`-Payload.
 */
function detailPayload(
    ?int $id,
    string $name,
    string $description = '',
    int $quantity = 1,
    array $properties = [],
    ?int $statusId = null,
): array {
    /** @var \Tests\TestCase $self */
    $self = test();
    $statusId ??= $self->status->id;

    $entry = [
        'name' => $name,
        'description' => $description,
        'quantity' => $quantity,
        'properties' => $properties,
        'status' => ['id' => $statusId],
    ];

    if ($id !== null) {
        $entry['id'] = $id;
    }

    return $entry;
}

/**
 * Hilfsfunktion: Baut einen UpdateInventoryArticleRequest mit dem gegebenen DetailArticle-Payload
 * und dem auf dem Test-Setup definierten Hauptartikel.
 */
function buildUpdateRequest(array $detailedArticleQuantities, array $overrides = []): UpdateInventoryArticleRequest
{
    /** @var \Tests\TestCase $self */
    $self = test();

    $payload = array_merge([
        'name' => $self->article->name,
        'description' => $self->article->description ?? '',
        'inventory_category_id' => $self->category->id,
        'quantity' => $self->article->quantity,
        'is_detailed_quantity' => true,
        'properties' => [],
        'detailed_article_quantities' => $detailedArticleQuantities,
        'statusValues' => [],
        'main_image_index' => 0,
    ], $overrides);

    return UpdateInventoryArticleRequest::create('/test', 'PATCH', $payload);
}

it('keeps existing detail article ids and inventory numbers when nothing about them changes', function (): void {
    $service = app(InventoryArticleService::class);

    $request = buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]);

    $service->update($this->article, $request);

    $fresh = $this->article->fresh('detailedArticleQuantities');

    expect($fresh->detailedArticleQuantities)->toHaveCount(3);
    expect($fresh->detailedArticleQuantities->pluck('id')->all())
        ->toEqualCanonicalizing([$this->detail1->id, $this->detail2->id, $this->detail3->id]);
    expect($fresh->detailedArticleQuantities->pluck('inventory_number')->all())
        ->toEqualCanonicalizing([
            $this->detail1->inventory_number,
            $this->detail2->inventory_number,
            $this->detail3->inventory_number,
        ]);
    expect($fresh->detailedArticleQuantities->pluck('detail_number')->all())
        ->toEqualCanonicalizing([1, 2, 3]);
});

it('updates fields of existing detail articles by id', function (): void {
    $service = app(InventoryArticleService::class);

    $request = buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A neu', 'geändert', 7),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]);

    $service->update($this->article, $request);

    $reloaded = $this->detail1->fresh();

    expect($reloaded->name)->toBe('Stuhl A neu');
    expect($reloaded->description)->toBe('geändert');
    expect($reloaded->quantity)->toEqual(7);
    expect($reloaded->detail_number)->toBe(1);
    expect($reloaded->inventory_number)->toBe($this->detail1->inventory_number);
});

it('soft-deletes detail articles missing from the request and keeps their inventory numbers locked', function (): void {
    $service = app(InventoryArticleService::class);

    // detail2 wird im Request weggelassen → muss soft-deleted werden
    $request = buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]);

    $service->update($this->article, $request);

    expect(InventoryDetailedQuantityArticle::find($this->detail2->id))->toBeNull();

    $trashed = InventoryDetailedQuantityArticle::withTrashed()->find($this->detail2->id);
    expect($trashed)->not->toBeNull();
    expect($trashed->detail_number)->toBe(2);
});

it('does not reuse detail numbers when soft-deleted detail articles exist', function (): void {
    $service = app(InventoryArticleService::class);

    // 1) detail2 soft-deleten
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]));

    // 2) Neues DetailArticle ohne id hinzufügen → muss detail_number 4 bekommen, NICHT 2
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        detailPayload(null, 'Stuhl D', 'neu'),
    ]));

    $newest = $this->article->detailedArticleQuantities()->where('name', 'Stuhl D')->first();

    expect($newest)->not->toBeNull();
    expect($newest->detail_number)->toBe(4);
    expect($newest->inventory_number)->toBe($this->article->inventory_number . '-004');
    expect($newest->external_id)->toBe($this->article->external_id . '-4');
});

it('synchronizes detail article properties via pivot sync (add, update, remove)', function (): void {
    $service = app(InventoryArticleService::class);
    $propAttach = [['id' => $this->property->id, 'value' => 'Holz']];

    // 1) Property an detail1 anhängen
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster', 1, $propAttach),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]));

    $detail1 = $this->detail1->fresh('properties');
    expect($detail1->properties)->toHaveCount(1);
    expect($detail1->properties->first()->pivot->value)->toBe('Holz');

    // 2) Wert ändern
    $propUpdate = [['id' => $this->property->id, 'value' => 'Metall']];
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster', 1, $propUpdate),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]));

    $detail1 = $this->detail1->fresh('properties');
    expect($detail1->properties->first()->pivot->value)->toBe('Metall');

    // 3) Property entfernen → leeres properties-Array
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
    ]));

    $detail1 = $this->detail1->fresh('properties');
    expect($detail1->properties)->toHaveCount(0);
});

it('creates new detail articles without an id and assigns the next free number', function (): void {
    $service = app(InventoryArticleService::class);

    // detail3 wird weggelassen + zwei neue (ohne id) hinzugefügt
    $service->update($this->article, buildUpdateRequest([
        detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
        detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
        detailPayload(null, 'Stuhl X', 'neu1'),
        detailPayload(null, 'Stuhl Y', 'neu2'),
    ]));

    $mainInventoryNumber = $this->article->inventory_number;
    $mainExternalId = $this->article->external_id;

    $stuhlX = $this->article->detailedArticleQuantities()->where('name', 'Stuhl X')->first();
    $stuhlY = $this->article->detailedArticleQuantities()->where('name', 'Stuhl Y')->first();

    expect($stuhlX->detail_number)->toBe(4);
    expect($stuhlX->inventory_number)->toBe($mainInventoryNumber . '-004');
    expect($stuhlX->external_id)->toBe($mainExternalId . '-4');
    expect($stuhlY->detail_number)->toBe(5);
    expect($stuhlY->inventory_number)->toBe($mainInventoryNumber . '-005');
    expect($stuhlY->external_id)->toBe($mainExternalId . '-5');
});

it('rejects detail article ids belonging to a different parent article via validation', function (): void {
    // anderer Artikel mit eigenem DetailArticle
    $otherArticle = InventoryArticle::factory()->create([
        'name' => 'Tisch',
        'inventory_category_id' => $this->category->id,
        'is_detailed_quantity' => true,
        'quantity' => 1,
    ]);
    $otherDetail = $otherArticle->detailedArticleQuantities()->create([
        'name' => 'Tisch A',
        'quantity' => 1,
        'inventory_article_status_id' => $this->status->id,
        'detail_number' => 1,
        'external_id' => TypeNumberGenerator::generateDetailExternalId($otherArticle->external_id, 1),
        'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($otherArticle->inventory_number, 1),
    ]);

    // Versuch, otherDetail-ID an unseren Artikel zu hängen
    $payload = [
        'name' => $this->article->name,
        'description' => '',
        'inventory_category_id' => $this->category->id,
        'quantity' => 3,
        'is_detailed_quantity' => true,
        'main_image_index' => 0,
        'detailed_article_quantities' => [[
            'id' => $otherDetail->id, // gehört zu einem ANDEREN Artikel
            'name' => 'Hijack',
            'description' => '',
            'quantity' => 1,
            'properties' => [],
            'status' => ['id' => $this->status->id],
        ]],
    ];

    $response = $this->patch(
        route('inventory-management.articles.update', $this->article->id),
        $payload
    );

    $response->assertSessionHasErrors('detailed_article_quantities.0.id');
    expect($otherDetail->fresh()->name)->toBe('Tisch A'); // unverändert
});
