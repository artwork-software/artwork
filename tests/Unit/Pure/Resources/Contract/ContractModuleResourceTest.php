<?php

namespace Tests\Unit\Pure\Resources\Contract;

use Artwork\Modules\Contract\Http\Resources\ContractModuleResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ContractModuleResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) ['id' => 8, 'name' => 'NDA'];

        $array = (new ContractModuleResource($model))->toArray(new Request());

        $this->assertSame(['id' => 8, 'name' => 'NDA'], $array);
    }

    #[Test]
    public function array_has_exactly_two_keys(): void
    {
        $array = (new ContractModuleResource((object) ['id' => 1, 'name' => '']))->toArray(new Request());
        $this->assertSame(['id', 'name'], array_keys($array));
    }
}
