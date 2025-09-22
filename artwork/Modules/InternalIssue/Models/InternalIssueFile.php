<?php

namespace Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class InternalIssueFile extends Model
{
    use HasFactory;

    protected $fillable = ['internal_issue_id', 'file_path', 'original_name'];

    protected $casts = [
        'created_at' => 'datetime:d.m.Y H:i',
    ];

    protected $guarded = [
        'id',
    ];


    public function issue()
    {
        return $this->belongsTo(InternalIssue::class, 'internal_issue_id');
    }
}
