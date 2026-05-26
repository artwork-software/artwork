<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiExportPreset extends Model
{
    use HasFactory;

    protected $table = 'bi_export_presets';

    protected $fillable = [
        'name',
        'columns',
        'created_by',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id', 'users');
    }
}
