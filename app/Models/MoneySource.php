<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Searchable;


/**
 * @property int $id
 * @property string $name
 * @property float $amount
 * @property Date $start_date
 * @property Date $end_date
 * @property string $source_name
 * @property string $description
 * @property boolean $is_group
 * @property array $users
 * @property int $group_id
 */
class MoneySource extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'amount',
        'start_date',
        'end_date',
        'source_name',
        'description',
        'is_group',
        'users',
        'group_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'is_group' => $this->is_group
        ];
    }
}
