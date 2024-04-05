<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Models\Contract;
use App\Policies\ContractPolicy;
use Tests\TestCase;

class ContractPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $policy = new ContractPolicy();

        $this->assertTrue($policy->viewAny());
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $contract->accessingUsers()->attach($user->id);

        $policy = new ContractPolicy();

        $this->assertTrue($policy->view($user, $contract));
    }

    public function testCreate(): void
    {
        $policy = new ContractPolicy();

        $this->assertTrue($policy->create());
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $contract->accessingUsers()->attach($user->id);

        $policy = new ContractPolicy();

        $this->assertTrue($policy->update($user, $contract));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $contract->accessingUsers()->attach($user->id);

        $policy = new ContractPolicy();

        $this->assertTrue($policy->delete($user, $contract));
    }
}
