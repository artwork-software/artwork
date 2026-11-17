<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\SageNotAssignedData;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * „Papierkorb leeren“ der nicht zugeordneten Sage-Buchungen: die Route stand hinter der Parameter-Route,
 * Laravel band "force-all" als Buchung und lieferte für alle 404.
 */
final class SageTrashForceDeleteAllTest extends FeatureTestCase
{
    #[Test]
    public function emptying_the_sage_trash_removes_only_trashed_bookings(): void
    {
        $this->actingAsAdmin();
        $trashed = SageNotAssignedData::factory()->count(2)->create(['project_id' => null]);
        $trashed->each->delete();
        $active = SageNotAssignedData::factory()->create(['project_id' => null]);

        $this->delete(route('sageNotAssignedData.forceDeleteAll'))
            ->assertRedirect(route('sageNotAssignedData.trashed'));

        foreach ($trashed as $booking) {
            self::assertNull(SageNotAssignedData::withTrashed()->find($booking->id));
        }
        self::assertNotNull(SageNotAssignedData::find($active->id));
    }
}
