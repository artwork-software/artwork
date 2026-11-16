<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Anlegen/Bearbeiten einer Finanzierungsquelle übernahm die Personen-Einträge roh in den Pivot: ein
 * mitgeschicktes money_source_id legte Rechte an einer fremden Quelle an (wie zuvor in updateUsers).
 */
final class MoneySourceUserPivotHardeningTest extends FeatureTestCase
{
    #[Test]
    public function store_ignores_foreign_pivot_fields(): void
    {
        $this->actingAsAdmin();
        $foreignSource = MoneySource::factory()->create();
        $person = User::factory()->create();

        $this->post(route('money_sources.store'), [
            'name' => 'Quelle mit Person',
            'amount' => '100',
            'is_group' => false,
            'users' => [
                $person->id => [
                    'user_id' => $person->id,
                    'competent' => true,
                    'write_access' => true,
                    'money_source_id' => $foreignSource->id,
                ],
            ],
        ])->assertRedirect();

        $created = MoneySource::where('name', 'Quelle mit Person')->firstOrFail();
        self::assertSame(1, DB::table('money_source_users')->where('money_source_id', $created->id)->where('user_id', $person->id)->count());
        self::assertSame(0, DB::table('money_source_users')->where('money_source_id', $foreignSource->id)->count());
    }

    #[Test]
    public function update_ignores_foreign_pivot_fields_and_rejects_unknown_users(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create(['is_group' => false]);
        $foreignSource = MoneySource::factory()->create();
        $person = User::factory()->create();

        $this->patch(route('money_sources.update', $source), [
            'name' => 'Umbenannt',
            'amount' => '100',
            'is_group' => false,
            'users' => [
                $person->id => [
                    'user_id' => $person->id,
                    'competent' => false,
                    'write_access' => true,
                    'money_source_id' => $foreignSource->id,
                ],
            ],
        ])->assertSessionHasNoErrors();

        self::assertSame(0, DB::table('money_source_users')->where('money_source_id', $foreignSource->id)->count());
        self::assertSame(1, DB::table('money_source_users')->where('money_source_id', $source->id)->where('write_access', true)->count());

        $this->patch(route('money_sources.update', $source), [
            'name' => 'Umbenannt',
            'amount' => '100',
            'is_group' => false,
            'users' => [['user_id' => 999999999, 'competent' => true, 'write_access' => true]],
        ])->assertSessionHasErrors('users.0.user_id');
    }
}
