<?php

namespace Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property string $type
 * @property array|null $config
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<ExternalUser> $externalUsers
 * @property \Illuminate\Database\Eloquent\Collection<ExternalUserGroupMapping> $groupMappings
 */
class ExternalUserSource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'active',
        'type',
        'config',
    ];

    protected $casts = [
        'active' => 'boolean',
        'config' => 'encrypted:array',
    ];

    public function toArray(): array
    {
        $attributes = parent::toArray();
        $config = $this->config ?? [];

        unset($config['bind_password'], $config['client_secret']);

        $attributes['config'] = $config;

        return $attributes;
    }

    /**
     * Kanonischer Provider-Schlüssel für das auth_provider-Feld am User.
     */
    public function providerKey(): string
    {
        return $this->type === 'identity_provider' ? 'oidc' : 'ldap';
    }

    /**
     * Stabiler Issuer der Verbindung. Bei OIDC der konfigurierte bzw. aus der
     * Discovery-URL abgeleitete Issuer, bei LDAP der Host.
     */
    public function resolvedIssuer(): ?string
    {
        $config = $this->config ?? [];

        if ($this->type === 'ldap') {
            return $config['host'] ?? null;
        }

        if (!empty($config['issuer'])) {
            return $config['issuer'];
        }

        if (!empty($config['discovery_url'])) {
            return preg_replace('#/\.well-known/openid-configuration/?$#', '', (string) $config['discovery_url']);
        }

        return null;
    }

    /**
     * Pro Verbindung konfigurierbare Default-Rolle für die Provisionierung.
     */
    public function defaultRoleId(): ?int
    {
        $roleId = $this->config['default_role_id'] ?? null;

        return $roleId !== null && $roleId !== '' ? (int) $roleId : null;
    }

    public function externalUsers(): HasMany
    {
        return $this->hasMany(ExternalUser::class, 'source_id', 'id');
    }

    public function groupMappings(): HasMany
    {
        return $this->hasMany(ExternalUserGroupMapping::class, 'source_id', 'id');
    }
}
