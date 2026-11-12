<?php

use Artwork\Modules\Permission\Enums\ConsoleOnlyPermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Die KI-Schnittstelle (/api/v1/ai/*) verlangt seit 2026_11_12_100000 das Konsolen-Recht "ai access". Damit
 * bestehende KI-Schlüssel weiterlaufen, bekommen deren Besitzer:innen das Recht einmalig: gültige, nicht
 * widerrufene Tokens mit mindestens einem ai:*-Scope. Die kurzlebigen Sitzungs-Tokens der KI-Leiste
 * (ai_session_client_id) zählen nicht, sonst hätte jede Person, die die Leiste einmal benutzt hat, danach
 * Zugriff auf die KI-Verwaltung. Idempotent: vorhandene Zuordnungen bleiben unverändert.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', ConsoleOnlyPermissionEnum::AiAccess->value)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        $ownerIds = DB::table('oauth_access_tokens')
            ->whereNotNull('user_id')
            ->where('revoked', false)
            ->where(static fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where('scopes', 'like', '%"ai:%')
            ->when(
                $this->sessionClientId() !== null,
                fn ($query) => $query->where('client_id', '!=', $this->sessionClientId())
            )
            ->distinct()
            ->pluck('user_id');

        $morphClass = (new User())->getMorphClass();

        foreach ($ownerIds as $userId) {
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'model_type' => $morphClass,
                'model_id' => $userId,
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Bewusst leer: Zuordnungen lassen sich nicht von später per Konsole vergebenen unterscheiden.
    }

    private function sessionClientId(): ?string
    {
        $payload = DB::table('settings')
            ->where('group', 'ai_assistant')
            ->where('name', 'ai_session_client_id')
            ->value('payload');

        $clientId = $payload !== null ? json_decode((string) $payload, true) : null;

        return is_string($clientId) && $clientId !== '' ? $clientId : null;
    }
};
