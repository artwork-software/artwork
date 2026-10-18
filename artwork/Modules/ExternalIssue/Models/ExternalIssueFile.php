<?php

namespace Artwork\Modules\ExternalIssue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalIssueFile extends Model
{
    use HasFactory;

    protected $fillable = ['external_issue_id', 'file_path', 'original_name'];

    /**
     * @return BelongsTo<ExternalIssue, $this>
     */
    public function issue(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            ExternalIssue::class,
            'external_issue_id',
            'id',
            'external_issue'
        );
    }
}
