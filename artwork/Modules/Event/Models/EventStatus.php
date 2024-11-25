<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order', 'color'];
}
