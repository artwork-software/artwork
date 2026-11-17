<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Sage-Buchungen im Budget: nicht zugeordnete Buchungen löschen/wiederherstellen,
 * Kommentare an zugeordneten Buchungen löschen und zugeordnete Buchungen per Drag & Drop
 * in eine andere oder eine neue Zeile verschieben.
 */
final class SageBookingActionsTest extends FeatureTestCase
{
    private Table $table;

    private Column $sageColumn;

    private SubPosition $subPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $project = Project::factory()->create();
        $this->table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        // Reihenfolge = KTO, KST, Text, Wert; die Sage-Spalte zählt beim Anlegen neuer Zeilen nicht mit
        foreach (['KTO', 'KST', 'Text', 'Wert'] as $position => $name) {
            Column::factory()->create(['table_id' => $this->table->id, 'position' => $position, 'name' => $name]);
        }
        $this->sageColumn = Column::factory()->create([
            'table_id' => $this->table->id,
            'position' => 4,
            'type' => 'sage',
        ]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $this->table->id]);
        $this->subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
    }

    /**
     * Zeile mit KTO/KST und einer Sage-Zelle samt Buchungen.
     *
     * @return array{row: SubPositionRow, sageCell: ColumnCell, bookings: array<int, SageAssignedData>}
     */
    private function createRowWithBookings(string $kto, string $kst, int $bookingCount, int $position = 0): array
    {
        $row = SubPositionRow::factory()->create([
            'sub_position_id' => $this->subPosition->id,
            'position' => $position,
        ]);
        $columns = $this->table->columns()->whereNot('type', 'sage')->orderBy('id')->get();
        foreach ($columns as $index => $column) {
            ColumnCell::factory()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $row->id,
                'value' => match ($index) {
                    0 => $kto,
                    1 => $kst,
                    default => '0,00',
                },
            ]);
        }
        $sageCell = ColumnCell::factory()->create([
            'column_id' => $this->sageColumn->id,
            'sub_position_row_id' => $row->id,
            'value' => '0',
        ]);

        $bookings = [];
        for ($index = 0; $index < $bookingCount; $index++) {
            $bookings[] = SageAssignedData::factory()->create([
                'column_cell_id' => $sageCell->id,
                'sa_kto' => $kto,
                'kto_soll' => '',
                'kst_stelle' => $kst,
                'buchungstext' => 'Buchung ' . $index,
            ]);
        }

        return ['row' => $row, 'sageCell' => $sageCell, 'bookings' => $bookings];
    }

    // ---------- nicht zugeordnete Buchungen ----------

    #[Test]
    public function destroying_not_assigned_sage_data_moves_it_to_the_trash(): void
    {
        $this->actingAsAdmin();
        $data = SageNotAssignedData::factory()->create(['project_id' => $this->table->project_id]);

        $this->delete(route('sageNotAssignedData.destroy', $data->id))->assertRedirect();

        $this->assertSoftDeleted($data);
    }

    #[Test]
    public function restoring_trashed_sage_data_brings_it_back(): void
    {
        $this->actingAsAdmin();
        $data = SageNotAssignedData::factory()->create(['project_id' => null]);
        $data->delete();

        $this->patch(route('sageNotAssignedData.restore', $data->id))->assertRedirect();

        $this->assertNotSoftDeleted($data);
    }

    #[Test]
    public function force_deleting_trashed_sage_data_removes_it_permanently(): void
    {
        $this->actingAsAdmin();
        $data = SageNotAssignedData::factory()->create(['project_id' => null]);
        $other = SageNotAssignedData::factory()->create(['project_id' => null]);
        $data->delete();

        $this->delete(route('sageNotAssignedData.forceDelete', $data->id))->assertRedirect();

        $this->assertDatabaseMissing('sage_not_assigned_data', ['id' => $data->id]);
        $this->assertNotSoftDeleted($other);
    }

    #[Test]
    public function destroying_unknown_sage_data_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('sageNotAssignedData.destroy', 999999999))->assertNotFound();
    }

    // ---------- Kommentare an zugeordneten Buchungen ----------

    #[Test]
    public function destroying_a_sage_assigned_data_comment_removes_only_that_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $booking = $this->createRowWithBookings('4000', '100', 1)['bookings'][0];
        $comment = SageAssignedDataComment::factory()->create([
            'sage_assigned_data_id' => $booking->id,
            'user_id' => $admin->id,
        ]);
        $otherComment = SageAssignedDataComment::factory()->create([
            'sage_assigned_data_id' => $booking->id,
            'user_id' => $admin->id,
        ]);

        $this->delete(route('sageAssignedDataComments.destroy', $comment->id))->assertSuccessful();

        $this->assertDatabaseMissing('sage_assigned_data_comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('sage_assigned_data_comments', ['id' => $otherComment->id]);
        $this->assertDatabaseHas('sage_assigned_data', ['id' => $booking->id]);
    }

    // ---------- Buchungen in eine bestehende Zeile verschieben ----------

    #[Test]
    public function moving_a_booking_onto_a_row_with_same_kto_and_kst_reassigns_it_and_drops_the_empty_source_row(): void
    {
        $this->actingAsAdmin();
        $target = $this->createRowWithBookings('4000', '100', 0);
        $source = $this->createRowWithBookings('4000', '100', 1, 1);

        $this->post(route('project.budget.move.sage.row', [
            'columnCell' => $target['sageCell']->id,
            'movedColumn' => $source['sageCell']->id,
        ]), ['multiple' => false])->assertRedirect();

        $this->assertSame($target['sageCell']->id, $source['bookings'][0]->fresh()->column_cell_id);
        $this->assertSoftDeleted('sub_position_rows', ['id' => $source['row']->id]);
        $this->assertNotSoftDeleted('sub_position_rows', ['id' => $target['row']->id]);
    }

    #[Test]
    public function moving_a_booking_onto_a_row_with_other_kto_is_rejected_with_an_error_message(): void
    {
        $this->actingAsAdmin();
        $target = $this->createRowWithBookings('5000', '100', 0);
        $source = $this->createRowWithBookings('4000', '100', 1, 1);

        $this->post(route('project.budget.move.sage.row', [
            'columnCell' => $target['sageCell']->id,
            'movedColumn' => $source['sageCell']->id,
        ]), ['multiple' => false])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($source['sageCell']->id, $source['bookings'][0]->fresh()->column_cell_id);
        $this->assertNotSoftDeleted('sub_position_rows', ['id' => $source['row']->id]);
    }

    #[Test]
    public function moving_selected_bookings_keeps_the_source_row_while_bookings_remain(): void
    {
        $this->actingAsAdmin();
        $target = $this->createRowWithBookings('4000', '100', 0);
        $source = $this->createRowWithBookings('4000', '100', 2, 1);
        [$moved, $staying] = $source['bookings'];

        $this->post(route('project.budget.move.sage.row', [
            'columnCell' => $target['sageCell']->id,
            'movedColumn' => $source['sageCell']->id,
        ]), ['multiple' => true, 'selectedData' => [$moved->id]])->assertRedirect();

        $this->assertSame($target['sageCell']->id, $moved->fresh()->column_cell_id);
        $this->assertSame($source['sageCell']->id, $staying->fresh()->column_cell_id);
        $this->assertNotSoftDeleted('sub_position_rows', ['id' => $source['row']->id]);
    }

    #[Test]
    public function moving_multiple_bookings_without_selection_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $target = $this->createRowWithBookings('4000', '100', 0);
        $source = $this->createRowWithBookings('4000', '100', 2, 1);

        $this->postJson(route('project.budget.move.sage.row', [
            'columnCell' => $target['sageCell']->id,
            'movedColumn' => $source['sageCell']->id,
        ]), ['multiple' => true])->assertUnprocessable()->assertJsonValidationErrors('selectedData');

        foreach ($source['bookings'] as $booking) {
            $this->assertSame($source['sageCell']->id, $booking->fresh()->column_cell_id);
        }
    }

    // ---------- Buchungen in eine neue Zeile verschieben ----------

    #[Test]
    public function moving_a_single_booking_to_a_new_row_creates_the_row_after_the_given_position(): void
    {
        $this->actingAsAdmin();
        $first = $this->createRowWithBookings('1000', '1', 0, 0);
        $second = $this->createRowWithBookings('2000', '2', 0, 1);
        $source = $this->createRowWithBookings('4000', '100', 1, 2);
        $booking = $source['bookings'][0];

        $this->post(route('project.budget.move.sage.to.row', [
            'table_id' => $this->table->id,
            'sub_position_id' => $this->subPosition->id,
            'positionBefore' => 0,
            'columnCell' => $source['sageCell']->id,
        ]), ['multiple' => false])->assertSuccessful();

        $newSageCell = ColumnCell::query()->findOrFail($booking->fresh()->column_cell_id);
        $newRow = $newSageCell->subPositionRow;
        $this->assertNotSame($source['row']->id, $newRow->id);
        $this->assertSame($this->subPosition->id, $newRow->sub_position_id);
        $this->assertSame(1, (int) $newRow->position);
        $this->assertSame(0, (int) $first['row']->fresh()->position);
        $this->assertSame(2, (int) $second['row']->fresh()->position);

        $values = $newRow->cells()->orderBy('column_id')->pluck('value')->all();
        $this->assertSame(['4000', '100', 'Buchung 0', '0,00', '0'], $values);
        $this->assertSoftDeleted('sub_position_rows', ['id' => $source['row']->id]);
    }

    #[Test]
    public function moving_a_cell_without_bookings_to_a_new_row_creates_no_empty_row(): void
    {
        $this->actingAsAdmin();
        $existing = $this->createRowWithBookings('1000', '1', 0, 0);
        $source = $this->createRowWithBookings('4000', '100', 0, 1);
        $rowCountBefore = SubPositionRow::query()->where('sub_position_id', $this->subPosition->id)->count();

        $this->post(route('project.budget.move.sage.to.row', [
            'table_id' => $this->table->id,
            'sub_position_id' => $this->subPosition->id,
            'positionBefore' => -1,
            'columnCell' => $source['sageCell']->id,
        ]), ['multiple' => false])->assertStatus(422);

        $this->assertSame(
            $rowCountBefore,
            SubPositionRow::query()->where('sub_position_id', $this->subPosition->id)->count()
        );
        $this->assertSame(0, (int) $existing['row']->fresh()->position);
    }

    #[Test]
    public function moving_to_a_new_row_in_a_sub_position_of_another_table_is_rejected(): void
    {
        $this->actingAsAdmin();
        $source = $this->createRowWithBookings('4000', '100', 1);
        $foreignSubPosition = SubPosition::factory()->create();

        $this->post(route('project.budget.move.sage.to.row', [
            'table_id' => $this->table->id,
            'sub_position_id' => $foreignSubPosition->id,
            'positionBefore' => -1,
            'columnCell' => $source['sageCell']->id,
        ]), ['multiple' => false])->assertNotFound();

        $this->assertSame(0, $foreignSubPosition->subPositionRows()->count());
        $this->assertSame($source['sageCell']->id, $source['bookings'][0]->fresh()->column_cell_id);
    }

    #[Test]
    public function moving_selected_bookings_to_a_new_row_moves_only_the_selection(): void
    {
        $this->actingAsAdmin();
        $source = $this->createRowWithBookings('4000', '100', 2);
        [$moved, $staying] = $source['bookings'];

        $this->post(route('project.budget.move.sage.to.row', [
            'table_id' => $this->table->id,
            'sub_position_id' => $this->subPosition->id,
            'positionBefore' => 0,
            'columnCell' => $source['sageCell']->id,
        ]), ['multiple' => true, 'selectedData' => [$moved->id]])->assertSuccessful();

        $this->assertNotSame($source['sageCell']->id, $moved->fresh()->column_cell_id);
        $this->assertSame($source['sageCell']->id, $staying->fresh()->column_cell_id);
        $this->assertNotSoftDeleted('sub_position_rows', ['id' => $source['row']->id]);
    }

    #[Test]
    public function moving_multiple_bookings_to_a_new_row_without_selection_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $source = $this->createRowWithBookings('4000', '100', 2);
        $rowCountBefore = SubPositionRow::query()->count();

        $this->postJson(route('project.budget.move.sage.to.row', [
            'table_id' => $this->table->id,
            'sub_position_id' => $this->subPosition->id,
            'positionBefore' => 0,
            'columnCell' => $source['sageCell']->id,
        ]), ['multiple' => true])->assertUnprocessable()->assertJsonValidationErrors('selectedData');

        $this->assertSame($rowCountBefore, SubPositionRow::query()->count());
    }
}
