<?php

namespace Tests\Unit\Pure\Resources\EventType;

use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventTypeResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_event_type_into_array(): void
    {
        $verifier = (object) [
            'id' => 7,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'profile_photo_url' => 'https://example.com/ada.png',
        ];

        $model = (object) [
            'id' => 5,
            'name' => 'Concert',
            'hex_code' => '#abcdef',
            'project_mandatory' => true,
            'individual_name' => false,
            'abbreviation' => 'CN',
            'relevant_for_project_period' => true,
            'verification_mode' => 'standard',
            'relevant_for_shift' => false,
            'relevant_for_inventory' => false,
            'specific_verifier_id' => null,
            'verifiers' => new Collection([$verifier]),
        ];

        $array = (new EventTypeResource($model))->toArray(new Request());

        $this->assertSame('EventTypeResource', $array['resource']);
        $this->assertSame(5, $array['id']);
        $this->assertSame('Concert', $array['name']);
        $this->assertSame('#abcdef', $array['hex_code']);
        $this->assertSame('CN', $array['abbreviation']);
        $this->assertTrue($array['project_mandatory']);
        $this->assertCount(1, $array['users']);
        $this->assertSame(7, $array['users'][0]['id']);
        $this->assertSame('Ada Lovelace', $array['users'][0]['full_name']);
        $this->assertSame('Ada Lovelace', $array['users'][0]['name']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(EventTypeResource::$wrap);
    }

    #[Test]
    public function empty_verifiers_results_in_empty_users(): void
    {
        $model = (object) [
            'id' => 1,
            'name' => 'X',
            'hex_code' => '#000',
            'project_mandatory' => false,
            'individual_name' => false,
            'abbreviation' => '',
            'relevant_for_project_period' => false,
            'verification_mode' => null,
            'relevant_for_shift' => false,
            'relevant_for_inventory' => false,
            'specific_verifier_id' => null,
            'verifiers' => new Collection(),
        ];

        $array = (new EventTypeResource($model))->toArray(new Request());

        $this->assertCount(0, $array['users']);
    }
}
