<?php

namespace Tests\Unit\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserContractAssignTest extends TestCase
{
    /**
     * Arbeitszeitmuster-Felder (valid_from, monday..sunday, work_time_pattern_id) sind KEINE Spalten
     * von user_contract_assigns. Sie dürfen nicht fillable sein – sonst schlägt create() mit
     * "Unknown column" fehl, sobald sie im Attribut-Array mitkommen.
     */
    #[Test]
    public function work_time_pattern_fields_are_not_fillable_and_are_ignored_on_create(): void
    {
        $user = User::factory()->create();

        $assign = UserContractAssign::create([
            'user_id' => $user->id,
            'free_full_days_per_week' => 2,
            'compensation_period' => 30,
            // Phantom-Felder: werden bei der Massenzuweisung verworfen
            'valid_from' => '2025-01-01',
            'valid_until' => null,
            'work_time_pattern_id' => 5,
            'monday' => '08:00',
        ]);

        $this->assertTrue($assign->exists);
        $this->assertSame(30, $assign->fresh()->compensation_period);

        foreach (['work_time_pattern_id', 'monday', 'sunday', 'valid_from', 'valid_until'] as $field) {
            $this->assertNotContains($field, $assign->getFillable(), "{$field} darf nicht fillable sein");
            $this->assertArrayNotHasKey($field, $assign->fresh()->getAttributes());
        }
    }
}
