<?php

namespace Artwork\Modules\MoneySourceReminder\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneySourceReminder extends Model
{
    use HasFactory;

    public const MONEY_SOURCE_REMINDER_TYPE_EXPIRATION = 'expiration';

    public const MONEY_SOURCE_REMINDER_TYPE_THRESHOLD = 'threshold';

    protected $fillable = [
        'money_source_id',
        'type',
        'value',
        'notification_created'
    ];

    public $timestamps = false;

    public function moneySource(): BelongsTo
    {
        return $this->belongsTo(
            MoneySource::class,
            'money_source_id',
            'id',
            'money_sources'
        );
    }
}
