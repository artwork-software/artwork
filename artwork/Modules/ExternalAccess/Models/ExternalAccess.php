<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\Artwork\Modules\ExternalAccess\Models\ExternalAccessFactory;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property int|null $crm_contact_id
 * @property int|null $invited_by_user_id
 * @property Carbon|null $crm_access_expires_at
 * @property Carbon|null $crm_expiry_reminder_sent_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_login_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CrmContact|null $crmContact
 * @property-read User|null $invitedBy
 */
class ExternalAccess extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authorizable;
    use HasFactory;
    use Notifiable;

    protected $table = 'external_accesses';

    protected $fillable = [
        'email',
        'name',
        'crm_contact_id',
        'invited_by_user_id',
        'crm_access_expires_at',
        'crm_expiry_reminder_sent_at',
        'revoked_at',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'crm_access_expires_at' => 'datetime',
            'crm_expiry_reminder_sent_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExternalAccessFactory
    {
        return ExternalAccessFactory::new();
    }

    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id', 'id', 'crmContact');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id', 'id', 'invitedBy');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(ExternalAccessScope::class, 'external_access_id');
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(ExternalLoginToken::class, 'external_access_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ExternalInvitation::class, 'external_access_id');
    }

    public function pendingSubmissions(): HasMany
    {
        return $this->hasMany(ExternalPendingSubmission::class, 'external_access_id');
    }

    /**
     * Anzeigename für Benachrichtigungen, Verlauf und Tooltips: hinterlegter Name, sonst der Name
     * des verknüpften CRM-Kontakts, sonst die E-Mail-Adresse.
     */
    public function displayName(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        return $this->crmContact?->display_name ?? $this->email;
    }

    public function isCrmAccessActive(): bool
    {
        // Reiner Tab-Zugang (ohne eigenen Kontakt) hat nie CRM-Selbstpflege
        if ($this->revoked_at !== null || $this->crm_contact_id === null) {
            return false;
        }
        return $this->crm_access_expires_at !== null
            && $this->crm_access_expires_at->isFuture();
    }

    public function hasAnyActiveAccess(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->isCrmAccessActive()) {
            return true;
        }

        return $this->scopes()
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->exists();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // No-op: external access uses magic-link only, no remember-me.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
