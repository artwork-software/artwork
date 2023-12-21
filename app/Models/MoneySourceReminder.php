<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneySourceReminder extends Model
{
    use HasFactory;

    public const MONEY_SOURCE_REMINDER_TYPE_EXPIRATION = 'expiration';

    public const MONEY_SOURCE_REMINDER_TYPE_THRESHOLD = 'threshold';

    protected $fillable = [
        'type',
        'value',
        'notification_created'
    ];

    public $timestamps = false;

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(MoneySource::class);
    }
}
