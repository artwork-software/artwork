<?php

namespace Artwork\Modules\WorkTime\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $booking_day
 * @property int|null $booking_weekday
 * @property int $wanted_working_hours
 * @property int $worked_hours
 * @property bool $is_special_day
 * @property int $nightly_working_hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $work_time_balance_change
 * @property-read \Artwork\Modules\User\Models\User $user
 * @property-read \Artwork\Modules\User\Models\User|null $booker
 */
class WorkTimeBooking extends Model
{
    /** @use HasFactory<\Database\Factories\WorkTimeBookingFactory> */
    use HasFactory;

    /**
     * $table->id();
     * $table->foreignId('user_id')
     * ->constrained('users')
     * ->onDelete('cascade');
     * $table->string('name')->nullable();
     * $table->text('comment')->nullable();
     * $table->date('booking_day')->nullable();
     * $table->integer('booking_weekday')->nullable();
     * $table->integer('wanted_working_hours')->default(0);
     * $table->integer('worked_hours')->default(0);
     * $table->boolean('is_special_day')->default(false);
     * $table->integer('nightly_working_hours')->default(0);
     * $table->timestamps();
     */

    protected $fillable = [
        'booker_id',
        'user_id',
        'name',
        'comment',
        'booking_day',
        'booking_weekday',
        'wanted_working_hours',
        'worked_hours',
        'is_special_day',
        'nightly_working_hours',
        'work_time_balance_change'
    ];

    protected $casts = [
        'booking_day' => 'date',
        'booking_weekday' => 'integer',
        'wanted_working_hours' => 'integer',
        'worked_hours' => 'integer',
        'is_special_day' => 'boolean',
        'nightly_working_hours' => 'integer'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'work_time_bookings'
        );
    }

    public function booker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'booker_id',
            'id',
            'work_time_bookings'
        );
    }
}
