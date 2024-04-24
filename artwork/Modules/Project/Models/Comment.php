<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Contract\Models\Traits\BelongsToContract;
use Artwork\Modules\MoneySourceFile\Models\MoneySourceFile;
use Artwork\Modules\Project\Models\Traits\BelongsToProject;
use Artwork\Modules\User\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $text
 * @property int $project_id
 * @property int $project_file_id
 * @property int $money_source_file_id
 * @property int $contract_id
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read Project $project
 */
class Comment extends Model
{
    use HasFactory;
    use BelongsToProject;
    use BelongsToUser;
    use BelongsToContract;
    use SoftDeletes;

    protected $fillable = [
        'text',
        'project_id',
        'project_file_id',
        'money_source_file_id',
        'contract_id',
        'user_id',
        'tab_id'
    ];

    protected $casts = [
        'created_at' => 'datetime: d. M Y H:i:s',
        'updated_at' => 'datetime',
    ];

    //@todo: fix phpcs error - refactor function name to projectFile
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function project_file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id', 'id', 'project_file');
    }

    //@todo: fix phpcs error - refactor function name to moneySourceFile
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function money_source_file(): BelongsTo
    {
        return $this->belongsTo(MoneySourceFile::class, 'money_source_file_id', 'id', 'money_source_file');
    }
}
