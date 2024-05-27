<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectComponentValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'project_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];


    protected $appends = [
        'text_without_html',
    ];

    public function getTextWithoutHtmlAttribute(): string
    {
        return strip_tags($this->data['text']);
    }
}
