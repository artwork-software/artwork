<?php

namespace Artwork\Modules\Permission\Enums;

/**
 * Rechte, die bewusst nur per Konsole (Tinker) vergeben werden.
 *
 * Sie stehen weder in PermissionEnum noch im Rechte-Katalog und tauchen daher nirgends in der
 * Oberfläche auf; der Rechte-Tab kann sie weder vergeben noch beim Speichern entziehen. Geprüft
 * werden sie ausschließlich als Direktrecht (hasDirectPermission) — weder eine Rolle noch der
 * Admin-Bypass aus Gate::before erfüllt sie.
 *
 * Vergabe: `User::find($id)->givePermissionTo(ConsoleOnlyPermissionEnum::AiAccess->value);`
 */
enum ConsoleOnlyPermissionEnum: string
{
    /** Zusätzliche Schranke vor der KI-Schnittstelle (/api/v1/ai/*) und der Verwaltung von KI-Schlüsseln. */
    case AiAccess = 'ai access';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
