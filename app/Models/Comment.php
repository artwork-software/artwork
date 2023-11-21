<?php

namespace App\Models;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'project_id',
        'project_file_id',
        'money_source_file_id',
        'contract_id',
        'user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function project_file()
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function money_source_file()
    {
        return $this->belongsTo(MoneySourceFile::class, 'money_source_file_id');
    }


}
