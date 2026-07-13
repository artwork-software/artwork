<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageBookingLog;
use Artwork\Modules\Budget\Models\SageBookingLogEntry;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Services\SageBookingLogRecorder;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SageBookingLogRecorderTest extends TestCase
{
    private SageBookingLogRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        // The recorder is a shared singleton; reset any state leaked from a previous test.
        $this->recorder = app(SageBookingLogRecorder::class);
        $this->recorder->finishRun();
    }

    protected function tearDown(): void
    {
        $this->recorder->finishRun();

        parent::tearDown();
    }

    private function booking(array $overrides = []): SageNotAssignedData
    {
        return new SageNotAssignedData(array_merge([
            'sage_id' => 4711,
            'tan' => 1,
            'periode' => 202601,
            'kto_haben' => '1200',
            'kreditor' => 'K1',
            'buchungstext' => 'test booking',
            'buchungsbetrag' => 12.34,
            'belegnummer' => 'B1',
            'belegdatum' => '05.01.2026',
            'kto_soll' => '1000',
            'sa_kto' => '',
            'kst_traeger' => 'R12364',
            'kst_stelle' => 'K100',
            'buchungsdatum' => '2026-01-05',
            'is_collective_booking' => false,
        ], $overrides));
    }

    #[Test]
    public function start_run_creates_a_log_header_and_marks_recording(): void
    {
        $user = User::factory()->create();

        $this->recorder->startRun('import', ['ktr' => 'R12364', 'from' => null], $user->id, 'specific_day_import');

        $this->assertTrue($this->recorder->isRecording());

        $log = SageBookingLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('import', $log->type);
        $this->assertSame('specific_day_import', $log->source);
        $this->assertSame($user->id, $log->user_id);
        // null criteria values are filtered out.
        $this->assertSame(['ktr' => 'R12364'], $log->criteria);
        $this->assertNotNull($log->started_at);
    }

    #[Test]
    public function import_run_records_created_and_updated_but_ignores_deleted(): void
    {
        $this->recorder->startRun('import', [], null, 'full_import');

        $this->recorder->record('created', $this->booking());
        $this->recorder->record('updated', $this->booking());
        $this->recorder->record('deleted', $this->booking()); // not tracked on an import run

        $this->recorder->finishRun();

        $log = SageBookingLog::latest('id')->first();
        $this->assertSame(1, $log->created_count);
        $this->assertSame(1, $log->updated_count);
        $this->assertSame(0, $log->deleted_count);
        $this->assertSame(2, $log->entries()->count());
        $this->assertNotNull($log->finished_at);
    }

    #[Test]
    public function delete_run_records_deleted_but_ignores_created_and_updated(): void
    {
        $this->recorder->startRun('delete', ['ktr' => 'R12364'], null, 'delete_bookings');

        $this->recorder->record('created', $this->booking());
        $this->recorder->record('updated', $this->booking());
        $this->recorder->record('deleted', $this->booking());

        $this->recorder->finishRun();

        $log = SageBookingLog::latest('id')->first();
        $this->assertSame(0, $log->created_count);
        $this->assertSame(0, $log->updated_count);
        $this->assertSame(1, $log->deleted_count);
        $this->assertSame(1, $log->entries()->count());
    }

    #[Test]
    public function entry_snapshot_captures_booking_fields_and_target_type(): void
    {
        $this->recorder->startRun('delete', [], null, 'delete_bookings');
        $this->recorder->record('deleted', $this->booking(['sage_id' => 999, 'kst_traeger' => 'ABC']));
        $this->recorder->finishRun();

        $entry = SageBookingLogEntry::latest('id')->first();
        $this->assertSame('deleted', $entry->action);
        $this->assertSame('not_assigned', $entry->target_type);
        $this->assertSame(999, (int) $entry->sage_id);
        $this->assertSame('ABC', $entry->kst_traeger);
    }

    #[Test]
    public function assigned_data_is_recorded_with_assigned_target_type(): void
    {
        $this->recorder->startRun('import', [], null, 'full_import');
        $this->recorder->record('created', new SageAssignedData([
            'sage_id' => 5,
            'kst_traeger' => 'R1',
            'buchungstext' => 'assigned',
            'buchungsbetrag' => 1,
            'buchungsdatum' => '2026-01-05',
            'column_cell_id' => null,
        ]));
        $this->recorder->finishRun();

        $entry = SageBookingLogEntry::latest('id')->first();
        $this->assertSame('assigned', $entry->target_type);
    }

    #[Test]
    public function record_is_a_noop_when_no_run_is_active(): void
    {
        $before = SageBookingLogEntry::count();

        $this->recorder->record('created', $this->booking());

        $this->assertSame($before, SageBookingLogEntry::count());
    }

    #[Test]
    public function finish_run_resets_the_recording_state(): void
    {
        $this->recorder->startRun('import', [], null, 'full_import');
        $this->assertTrue($this->recorder->isRecording());

        $this->recorder->finishRun();

        $this->assertFalse($this->recorder->isRecording());
    }

    #[Test]
    public function a_failing_entry_insert_never_throws_or_rolls_back_the_wrapping_transaction(): void
    {
        // Guarantee the entry insert fails regardless of the connection's default sql_mode.
        DB::statement("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");

        $this->recorder->startRun('import', [], null, 'full_import');

        // Mimic importRegularBookings(): each booking runs inside its own transaction and a
        // logging failure must not roll it back.
        DB::beginTransaction();
        $threw = false;
        try {
            // buchungstext far exceeds the VARCHAR(255) snapshot column -> insert fails.
            $this->recorder->record('created', $this->booking(['buchungstext' => str_repeat('x', 6000)]));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $threw = true;
        }

        $this->assertFalse($threw, 'record() must swallow logging failures instead of throwing');

        $this->recorder->finishRun();

        // The failed entry was simply skipped; nothing was recorded, but the run still closed cleanly.
        $log = SageBookingLog::latest('id')->first();
        $this->assertSame(0, $log->created_count);
        $this->assertNotNull($log->finished_at);
    }

    #[Test]
    public function deleting_a_log_cascades_to_its_entries(): void
    {
        $this->recorder->startRun('delete', [], null, 'delete_bookings');
        $this->recorder->record('deleted', $this->booking());
        $this->recorder->finishRun();

        $log = SageBookingLog::latest('id')->first();
        $logId = $log->id;
        $this->assertSame(1, SageBookingLogEntry::where('sage_booking_log_id', $logId)->count());

        $log->delete();

        $this->assertSame(0, SageBookingLogEntry::where('sage_booking_log_id', $logId)->count());
    }
}
