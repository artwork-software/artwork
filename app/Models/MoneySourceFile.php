<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoneySourceFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id'
    ];

    public function money_source() {
        return $this->belongsTo(MoneySource::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

}
