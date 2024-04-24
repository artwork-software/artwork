<?php

namespace Artwork\Modules\MoneySourceFile\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $money_source_id
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class MoneySourceFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'basename',
        'money_source_id'
    ];

    protected $guarded = [
        'id'
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

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
