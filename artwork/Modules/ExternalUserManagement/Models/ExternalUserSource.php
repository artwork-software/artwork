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
     * Ob die Quelle neue Konten auch ohne email_verified-Claim anlegen darf. Microsoft Entra
     * liefert den Claim in der Regel nicht; Bestandsquellen wurden per Migration auf true gesetzt.
     */
    public function trustsIdpEmail(): bool
    {
        return (bool) ($this->config['trust_idp_email'] ?? false);
    }

    /**
     * Prüft die E-Mail gegen die Domain-Allowlist der Quelle. Wie sich eine leere Allowlist
     * verhält, entscheidet der Aufrufer: OIDC sperrt dann, LDAP lässt alles zu.
     */
    public function allowsEmailDomain(?string $email, bool $allowWhenListEmpty): bool
    {
        $allowed = $this->config['allowed_domains'] ?? [];

        if (empty($allowed)) {
            return $allowWhenListEmpty;
        }

        if ($email === null || $email === '') {
            return false;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '@', 1));
        $allowed = array_map(
            static fn ($entry): string => strtolower(ltrim(trim((string) $entry), '@')),
            (array) $allowed
        );

        return in_array($domain, $allowed, true);
    }

    /**
     * Pro Verbindung konfigurierbare Default-Rolle für die Provisionierung.
     */
    public function defaultRoleId(): ?int
    {
        $roleId = $this->config['default_role_id'] ?? null;

        return $roleId !== null && $roleId !== '' ? (int) $roleId : null;
    }

    /**
     * @return HasMany<ExternalUser, $this>
     */
    public function externalUsers(): HasMany
    {
        return $this->hasMany(ExternalUser::class, 'source_id', 'id');
    }

    /**
     * @return HasMany<ExternalUserGroupMapping, $this>
     */
    public function groupMappings(): HasMany
    {
        return $this->hasMany(ExternalUserGroupMapping::class, 'source_id', 'id');
    }
}
