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

    protected $with = ['craft', 'employees', 'masters'];

    protected $appends= ['break_formatted', 'employee_count', 'empty_employee_count', 'master_count', 'empty_master_count'];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function craft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Craft::class)->without(['users']);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id');
    }

    public function masters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_master', 'shift_id', 'user_id');
    }

    public function getEmptyEmployeeCountAttribute(){
        $employeeCount = $this->employees()->count();
        return $this->number_employees - $employeeCount;
    }

    public function getEmployeeCountAttribute(){
        return $this->employees()->count();
    }

    public function getEmptyMasterCountAttribute(){
        $masterCount = $this->masters()->count();
        return $this->number_masters - $masterCount;
    }

    public function getMasterCountAttribute(){
        return $this->masters()->count();
    }

    public function getBreakFormattedAttribute(): string
    {
        $hours = intdiv($this->break_minutes, 60).':'. ($this->break_minutes % 60);
        return Carbon::parse($hours)->format('H:i');
    }
}
