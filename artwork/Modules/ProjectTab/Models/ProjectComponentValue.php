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
        // check if the data is an array and has a key 'text'
        if (!is_array($this->data) || !array_key_exists('text', $this->data)) {
            return '';
        }
        return strip_tags($this->data['text']);
    }
}
