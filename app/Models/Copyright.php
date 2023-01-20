<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copyright extends Model
{
    use HasFactory;

    protected $fillable = [
        // Urheberrecht ja/nein
        'own_copyright',
        'live_music',
        // Verwertungsgesellschaft
        'collecting_society',
        // Großes oder kleines Recht
        'law_size',
        'project_id'
    ];

    protected $casts = [
        'own_copyright' => 'boolean',
        'live_music' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
