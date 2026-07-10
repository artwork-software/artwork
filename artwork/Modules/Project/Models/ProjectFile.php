<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Traits\BelongsToProject;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $project_id
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class ProjectFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToProject;
    use SoftDeletes;

    protected $fillable = [
        'tab_id',
        'name',
        'basename',
        'project_id',
    ];

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'file_size',
        'storage_available',
    ];

    private bool $storedFileSizeResolved = false;

    private ?int $storedFileSizeInBytes = null;

    public function accessingUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function getFileSizeAttribute(): ?string
    {
        $fileSizeInBytes = $this->resolveStoredFileSizeInBytes();

        if ($fileSizeInBytes === null) {
            return null;
        }

        $fileSizeInKB = $fileSizeInBytes / 1024; // Bytes zu MB
        return number_format($fileSizeInKB, 2) . ' Kb'; // Formatieren auf 2 Nachkommastellen
    }

    public function getStorageAvailableAttribute(): bool
    {
        return $this->resolveStoredFileSizeInBytes() !== null;
    }

    private function resolveStoredFileSizeInBytes(): ?int
    {
        if ($this->storedFileSizeResolved) {
            return $this->storedFileSizeInBytes;
        }

        $this->storedFileSizeResolved = true;

        try {
            $this->storedFileSizeInBytes = Storage::fileSize('project_files/' . $this->basename);
        } catch (FilesystemException) {
            return null;
        }

        return $this->storedFileSizeInBytes;
    }
}
