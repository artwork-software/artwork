<?php

namespace App\Models;

use App\Casts\TimeWithoutSeconds;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'start',
        'end',
        'break_minutes',
        'craft_id',
        'number_employees',
        'number_masters',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    protected $with = ['craft', 'users'];

    protected $appends= ['break_formatted', 'user_count', 'empty_user_count', 'empty_master_count', 'master_count', 'masters', 'employees'];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function craft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Craft::class)->without(['users']);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id')->withPivot([
            'is_master'
        ])->orderByPivot('is_master', 'desc');
    }

    public function getMasterCountAttribute(): int
    {
        return $this->users()->wherePivot('is_master', true)->count();
    }

    public function getEmptyMasterCountAttribute(){
        $masterCount = $this->users()->wherePivot('is_master', true)->count();
        return $this->number_masters - $masterCount;
    }

    public function getEmptyUserCountAttribute(){
        $employeeCount = $this->users()->wherePivot('is_master', false)->count();
        return $this->number_employees - $employeeCount;
    }

    public function getUserCountAttribute(): int
    {
        return $this->users()->wherePivot('is_master', false)->count();
    }

    public function getMastersAttribute()
    {
        return $this->users()->wherePivot('is_master', true)->get();
    }

    public function getEmployeesAttribute()
    {
        return $this->users()->wherePivot('is_master', false)->get();
    }

    public function getBreakFormattedAttribute(): string
    {
        $hours = intdiv($this->break_minutes, 60).':'. ($this->break_minutes % 60);
        return Carbon::parse($hours)->format('H:i');
    }
}
