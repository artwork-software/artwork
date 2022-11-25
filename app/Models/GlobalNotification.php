<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalNotification extends Model
{
    use HasFactory;

    protected $fillable = [
      'title',
      'description',
      'image_name',
      'expiration_date',
      'created_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}

