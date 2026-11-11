<?php

namespace Tests\Feature\Modules\Contract;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Vertragsarten, Unternehmensformen und Währungen lassen sich nur endgültig löschen, solange kein
 * Vertrag sie verwendet; sonst bleibt der Eintrag im Papierkorb und es erscheint eine Meldung.
 */
final class ContractMasterDataForceDeleteTest extends FeatureTestCase
{
    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function masterData(): array
    {
        return [
            'contract type' => [ContractType::class, 'contract_type_id', 'contract_types.force'],
            'company type' => [CompanyType::class, 'company_type_id', 'company_types.force'],
            'currency' => [Currency::class, 'currency_id', 'currencies.force'],
        ];
    }

    #[Test]
    #[DataProvider('masterData')]
    public function force_delete_is_refused_while_a_contract_uses_the_entry(
        string $modelClass,
        string $foreignKey,
        string $routeName
    ): void {
        $this->actingAsAdmin();
        $entry = $modelClass::factory()->create();
        Contract::factory()->create([$foreignKey => $entry->id]);
        $entry->delete();

        $this->delete(route($routeName, $entry->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotNull($modelClass::onlyTrashed()->find($entry->id));
    }

    #[Test]
    #[DataProvider('masterData')]
    public function force_delete_removes_an_unused_entry(
        string $modelClass,
        string $foreignKey,
        string $routeName
    ): void {
        $this->actingAsAdmin();
        $entry = $modelClass::factory()->create();
        $entry->delete();

        $this->delete(route($routeName, $entry->id))
            ->assertRedirect(route('projects.settings.trashed'))
            ->assertSessionMissing('error');

        $this->assertNull($modelClass::withTrashed()->find($entry->id));
    }
}
