<?php

namespace Artwork\Modules\AiAssistant\Support;

/**
 * Die Scopes des KI-Assistenten und die Regel, dass sie fuer Kunden-API-Keys nicht waehlbar sind
 * (core-session-token; Konzept C2 §3).
 *
 * Ein Haus-Admin koennte sonst einen ein Jahr gueltigen Personal-Access-Token mit ai:*-Scopes
 * erzeugen und den KI-Dienst ohne Session-Bindung ansprechen. Der Filter steht hier an einer
 * Stelle, weil ihn zwei Stellen brauchen: die Validierung der Token-Vergabe und die Scope-Liste
 * in den Tooleinstellungen.
 */
final class AiScopes
{
    public const PREFIX = 'ai:';

    public const TRANSCRIPT = 'ai:transcript';
    public const READ = 'ai:read';
    public const WRITE = 'ai:write';

    /** @return string[] */
    public static function all(): array
    {
        return [self::TRANSCRIPT, self::READ, self::WRITE];
    }

    public static function isAiScope(string $scope): bool
    {
        return str_starts_with($scope, self::PREFIX);
    }

    /**
     * KI-Key = Token mit mindestens einem ai:*-Scope (Übersicht der Schnittstellen-Seite).
     *
     * @param array<int, mixed> $scopeIds
     */
    public static function containsAiScope(array $scopeIds): bool
    {
        foreach ($scopeIds as $scope) {
            if (is_string($scope) && self::isAiScope($scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $scopeIds
     * @return string[]
     */
    public static function excludeFrom(array $scopeIds): array
    {
        return array_values(array_filter(
            $scopeIds,
            static fn (string $scope): bool => !self::isAiScope($scope)
        ));
    }
}
