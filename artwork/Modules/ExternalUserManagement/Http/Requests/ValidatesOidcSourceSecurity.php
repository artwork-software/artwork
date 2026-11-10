<?php

namespace Artwork\Modules\ExternalUserManagement\Http\Requests;

use Artwork\Modules\ExternalUserManagement\Support\OidcProviderPreset;
use Closure;

/**
 * Sicherheitsregeln für OIDC-Quellen: keine Entra-Multi-Tenant-Endpunkte (dort könnte sich jedes
 * fremde Tenant-Konto anmelden) und nur unveränderliche Kennungen (sub/oid). Eine bereits
 * gespeicherte andere Kennung darf beim Bearbeiten stehen bleiben, damit Bestandsverknüpfungen
 * nicht abreißen.
 */
trait ValidatesOidcSourceSecurity
{
    protected function singleTenantRule(): Closure
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
        return function (string $attribute, mixed $value, Closure $fail): void {
            $isMultiTenant = str_ends_with($attribute, 'tenant_id')
                ? OidcProviderPreset::isMultiTenantTenantId((string) $value)
                : OidcProviderPreset::isMultiTenantUrl((string) $value);

            if ($isMultiTenant) {
                $fail(__('Multi-tenant endpoints (common, organizations, consumers) are not allowed. Enter your own tenant ID.'));
            }
        };
    }

    protected function identifierClaimRule(?string $storedIdentifier = null): Closure
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
        return function (string $attribute, mixed $value, Closure $fail) use ($storedIdentifier): void {
            if (in_array($value, OidcProviderPreset::ALLOWED_IDENTIFIER_CLAIMS, true)) {
                return;
            }

            if ($storedIdentifier !== null && $value === $storedIdentifier) {
                return;
            }

            $fail(__('Only the immutable claims sub or oid can be used as identifier.'));
        };
    }
}
