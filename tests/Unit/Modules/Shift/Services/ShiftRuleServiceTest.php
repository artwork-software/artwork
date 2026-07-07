<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftRuleServiceTest extends TestCase
{
    private ShiftRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftRuleService::class);
    }

    #[Test]
    public function get_active_rules_filters_inactive(): void
    {
        ShiftRule::factory()->count(2)->create(['is_active' => true]);
        ShiftRule::factory()->inactive()->create();

        $active = $this->service->getActiveRules();

        $this->assertGreaterThanOrEqual(2, $active->count());
        $this->assertTrue($active->every(fn ($rule) => (bool) $rule->is_active));
    }

    #[Test]
    public function get_all_with_relations_returns_collection(): void
    {
        ShiftRule::factory()->count(3)->create();

        $result = $this->service->getAllWithRelations();

        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function create_rule_persists_record(): void
    {
        $rule = $this->service->createRule([
            'name' => 'Max 8h',
            'description' => 'Maximum 8 hours per day',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true,
        ]);

        $this->assertNotNull($rule->id);
        $this->assertSame('Max 8h', $rule->name);
        $this->assertSame('maxWorkingHoursOnDay', $rule->trigger_type);
    }

    #[Test]
    public function update_rule_persists_changes(): void
    {
        $rule = ShiftRule::factory()->create([
            'name' => 'Old',
            'is_active' => true,
        ]);

        $updated = $this->service->updateRule($rule, ['name' => 'New name']);

        $this->assertSame('New name', $updated->name);
        $this->assertDatabaseHas('shift_rules', ['id' => $rule->id, 'name' => 'New name']);
    }

    #[Test]
    public function delete_rule_removes_record(): void
    {
        $rule = ShiftRule::factory()->create();

        $this->service->deleteRule($rule);

        $this->assertSoftDeleted('shift_rules', ['id' => $rule->id]);
    }

    #[Test]
    public function get_available_rule_types_returns_expected_keys(): void
    {
        $types = $this->service->getAvailableRuleTypes();

        $this->assertIsArray($types);
        $this->assertContains('maxWorkingHoursOnDay', $types);
        $this->assertContains('maxConsecWorkingDays', $types);
        $this->assertContains('weeklyMaxHours', $types);
        $this->assertContains('restTimeBeforeWorkday', $types);
        $this->assertContains('restTimeBeforeHoliday', $types);
        $this->assertContains('minDaysBeforeCommit', $types);
    }

    #[Test]
    public function map_violations_to_array_returns_collection_of_arrays(): void
    {
        $rule = ShiftRule::factory()->create();
        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
        ]);

        $result = $this->service->mapViolationsToArray(collect([$violation->fresh(['user', 'shiftRule'])]));

        $this->assertCount(1, $result);
        $first = $result->first();
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('rule_name', $first);
        $this->assertArrayHasKey('violation_date', $first);
        $this->assertArrayHasKey('severity', $first);
    }
}
