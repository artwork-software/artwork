<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\Traits\LogsShiftPlanDayActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShiftPlanComment extends Model
{
    use HasFactory;
    use LogsActivity;
    use LogsShiftPlanDayActivity;

    protected $fillable = ['comment', 'date', 'created_by'];

    /**
     * Notizen zu einem Tag einer Person: Anlegen, Ändern und Löschen erscheinen im Schichtverlauf
     * (wer, wann, Text).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('shift_plan_comment')
            ->logOnly(['comment', 'date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function shiftHistoryDayEntryKind(): string
    {
        return 'note';
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function shiftHistoryPersonColumns(): array
    {
        return ['commentable_type', 'commentable_id'];
    }

    // Polymorphe Beziehung
    public function commentable()
    {
        return $this->morphTo();
    }
}
