<?php

namespace Artwork\Modules\Project\Models;

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

    /**
     * Liefert den Rohtext (historischer Name); strip_tags würde Eingaben wie "a<b" verstümmeln.
     */
    public function getTextWithoutHtmlAttribute(): string
    {
        if (!is_array($this->data) || !array_key_exists('text', $this->data)) {
            return '';
        }
        return (string) ($this->data['text'] ?? '');
    }
}
