/**
 * Zugriff auf localStorage/sessionStorage, der nie wirft: In gesperrten Profilen (Cookies blockiert,
 * manche Firmen-/Privatsphäre-Einstellungen) wirft schon der Zugriff auf window.localStorage einen
 * SecurityError – beim Start blieb die Seite dann weiß.
 */
function resolveStorage(kind) {
    try {
        return kind === 'session' ? window.sessionStorage : window.localStorage;
    } catch {
        return null;
    }
}

export function safeGetItem(key, kind = 'local') {
    try {
        return resolveStorage(kind)?.getItem(key) ?? null;
    } catch {
        return null;
    }
}

export function safeSetItem(key, value, kind = 'local') {
    const storage = resolveStorage(kind);
    if (!storage) {
        return false;
    }
    try {
        storage.setItem(key, value);
        return true;
    } catch {
        return false;
    }
}

export function safeRemoveItem(key, kind = 'local') {
    try {
        resolveStorage(kind)?.removeItem(key);
    } catch {
        // gesperrter Speicher: nichts zu entfernen
    }
}
