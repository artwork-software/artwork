<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Traits\BelongsToProject;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $project_id
 * @property int|null $tab_id
 * @property bool $is_budget_document Budget-Dokument mit Freigabeliste (nur Freigegebene und Admins)
 * @property string $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
        'external_access_id',
        'is_budget_document',
    ];

    protected $casts = [
        'is_budget_document' => 'boolean',
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

    /**
     * @return BelongsToMany<User, $this>
     */
    public function accessingUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Externe Person (Magic-Link-Zugang), die die Datei über einen freigegebenen Tab hochgeladen hat.
     * @return BelongsTo<\Artwork\Modules\ExternalAccess\Models\ExternalAccess, $this>
     */
    public function externalAccess(): BelongsTo
    {
        return $this->belongsTo(
            \Artwork\Modules\ExternalAccess\Models\ExternalAccess::class,
            'external_access_id',
            'id',
            'externalAccess'
        );
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
            $this->storedFileSizeInBytes = Storage::fileSize($this->storagePath());
        } catch (FilesystemException) {
            return null;
        }

        return $this->storedFileSizeInBytes;
    }

    public function storagePath(): string
    {
        return 'project_files/' . $this->basename;
    }
}
