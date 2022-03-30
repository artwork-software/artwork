<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name'
    ];

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function invitations() {
        return $this->belongsToMany(Invitation::class);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'logo_url'
    ];

    public function getLogoURLAttribute(): ?string
    {
        if($this->logo_path) {
            return Storage::disk('public')->url($this->logo_path);
        } else {
            return null;
        }

    }
}
