<?php

namespace Tests\Unit\Artwork\Modules\Contract\Models\Traits;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Models\Traits\BelongsToContract;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class BelongsToContractTest extends TestCase
{
    public function testContract(): void
    {
        $contract = Contract::factory()->create();
        $model = new class extends Model {
            use BelongsToContract;

            public int $contract_id;
        };
        $model->contract_id = $contract->id;
        $this->assertInstanceOf(Contract::class, $model->contract()->getRelated());
    }
}
