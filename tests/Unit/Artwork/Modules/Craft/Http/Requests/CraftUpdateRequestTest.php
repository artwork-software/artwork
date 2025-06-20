<?php

namespace Tests\Unit\Artwork\Modules\Craft\Http\Requests;

use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;

class CraftUpdateRequestTest extends TestCase
{
    public function testRules(): void
    {
        $rules = (new CraftUpdateRequest())->rules();

        $expectedRules = [
            'name' => 'required|string|min:1|max:255',
            'abbreviation' => 'required|string|min:1|max:3',
            'users' => 'array',
            'users_for_inventory' => 'array',
            'assignable_by_all' => 'required|boolean',
            'inventory_planned_by_all' => 'required|boolean',
            'universally_applicable' => 'required|boolean',
            'managersToBeAssigned' => 'sometimes|array',
            'managersToBeAssigned.*' => 'array',
            'managersToBeAssigned.*.manager_id' => 'required|integer',
        ];

        // Check all rules except the Rule::in() one
        foreach ($expectedRules as $key => $value) {
            $this->assertArrayHasKey($key, $rules);
            $this->assertSame($value, $rules[$key]);
        }

        // Check that the Rule::in() rule exists
        $this->assertArrayHasKey('managersToBeAssigned.*.manager_type', $rules);
        $this->assertInstanceOf(Rule\In::class, $rules['managersToBeAssigned.*.manager_type']);
    }
}
