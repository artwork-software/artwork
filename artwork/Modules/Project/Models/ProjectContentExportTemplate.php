<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vorlage des Projektinhalte-Exports (Excel und Word teilen sich die Vorlagen).
 *
 * @property int $id
 * @property string $name
 * @property array<int, array{key: string, title?: string|null, show_label?: bool}> $columns
 * @property array{
 *     filters?: array<string, mixed>,
 *     event_column?: array{event_type_ids?: array<int, int>}
 * }|null $settings
 * @property int|null $created_by
 */
class ProjectContentExportTemplate extends Model
{
    use HasFactory;

    protected $table = 'project_content_export_templates';

    protected $fillable = [
        'name',
        'columns',
        'settings',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
