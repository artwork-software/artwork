<?php

namespace Artwork\Modules\MoneySourceTask\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $money_source_id
 * @property string $name
 * @property string $description
 * @property Carbon $deadline
 * @property int $creator
 * @property string $created_at
 * @property string $updated_at
 * @property int $done
 */
class MoneySourceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'money_source_id',
        'name',
        'description',
        'deadline',
        'creator',
        'done'
    ];

    protected $casts = [
        'deadline' => 'datetime: d. F Y H:i:s'
    ];

    //@todo: fix phpcs error - refactor function name to moneySource
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function money_source(): BelongsTo
    {
        return $this->belongsTo(
            MoneySource::class,
            'money_source_id',
            'id',
            'money_sources'
        );
    }

    //@todo: fix phpcs error - refactor function name to moneySourceTaskUsers
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function money_source_task_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'money_source_task_user', 'task_id');
    }
}
