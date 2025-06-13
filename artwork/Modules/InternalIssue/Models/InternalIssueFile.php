<?php

namespace Artwork\Modules\InternalIssue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalIssueFile extends Model
{
    use HasFactory;

    protected $fillable = ['internal_issue_id', 'file_path', 'original_name'];

    public function issue()
    {
        return $this->belongsTo(InternalIssue::class, 'internal_issue_id');
    }
}
