<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * KI-Freischaltung (ARTWORK-352) außerhalb von Spatie: eigene Spalte statt Recht.
 *
 * Ein Spatie-Recht erscheint zwangsläufig in jeder Rechte-Liste (Shared-Props für Admins,
 * Profile, Rechte-Tab, Vorlagen, Gruppen-Mappings) und ist über jede dieser Oberflächen vergebbar.
 * Die Spalte ist weder fillable noch serialisierbar und wird nur per Konsole gesetzt
 * (User::grantAiAccess()).
 *
 * Übernimmt bestehende Vergaben des zwischenzeitlichen Rechts "ai access" und schaltet wie
 * 2026_11_12_165112 die Besitzer:innen gültiger KI-Schlüssel frei, ohne die kurzlebigen
 * Sitzungs-Tokens der KI-Leiste (greift so auch dort, wo das Recht nie angelegt wurde). Danach
 * entfernt sie "ai access" und das GUI-Recht "change ai assistant settings": die KI-Einstellungen
 * hängen jetzt an derselben Freischaltung.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const REMOVED_PERMISSIONS = ['ai access', 'change ai assistant settings'];

    public function up(): void
    {
        if (!Schema::hasColumn('users', 'ai_access_granted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('ai_access_granted_at')->nullable();
            });
        }

        $aiAccessPermissionIds = DB::table('permissions')->where('name', 'ai access')->pluck('id');

        if ($aiAccessPermissionIds->isNotEmpty()) {
            $userIds = DB::table('model_has_permissions')
                ->whereIn('permission_id', $aiAccessPermissionIds)
                ->where('model_type', 'like', '%User')
                ->pluck('model_id');

            DB::table('users')
                ->whereIn('id', $userIds)
                ->whereNull('ai_access_granted_at')
                ->update(['ai_access_granted_at' => now()]);
        }

        $this->grantOwnersOfAiKeys();

        $permissionIds = DB::table('permissions')->whereIn('name', self::REMOVED_PERMISSIONS)->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'ai_access_granted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('ai_access_granted_at');
            });
        }
    }

    private function grantOwnersOfAiKeys(): void
    {
        if (!Schema::hasTable('oauth_access_tokens')) {
            return;
        }

        $sessionClientId = $this->sessionClientId();

        $ownerIds = DB::table('oauth_access_tokens')
            ->whereNotNull('user_id')
            ->where('revoked', false)
            ->where(static fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where('scopes', 'like', '%"ai:%')
            ->when($sessionClientId !== null, fn ($query) => $query->where('client_id', '!=', $sessionClientId))
            ->distinct()
            ->pluck('user_id');

        DB::table('users')
            ->whereIn('id', $ownerIds)
            ->whereNull('ai_access_granted_at')
            ->update(['ai_access_granted_at' => now()]);
    }

    private function sessionClientId(): ?string
    {
        if (!Schema::hasTable('settings')) {
            return null;
        }

        $payload = DB::table('settings')
            ->where('group', 'ai_assistant')
            ->where('name', 'ai_session_client_id')
            ->value('payload');

        $clientId = $payload !== null ? json_decode((string) $payload, true) : null;

        return is_string($clientId) && $clientId !== '' ? $clientId : null;
    }
};
