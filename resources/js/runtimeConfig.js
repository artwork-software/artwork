/**
 * Accessor für die serverseitig injizierte Laufzeit-Config.
 *
 * Die Werte kommen aus config/frontend.php und werden in
 * resources/views/app.blade.php als window.__APP_CONFIG__ ausgeliefert — also pro
 * Request, nicht zur Build-Zeit. Dadurch bleibt das JS-Bundle umgebungsneutral und
 * kann einmal gebaut auf beliebigen Kundeninstallationen laufen.
 *
 * Das Global fehlt bewusst auf den External-Access-Seiten; deshalb ist der Zugriff
 * tolerant und faellt auf den uebergebenen Fallback zurueck.
 */
export function cfg(path, fallback = undefined) {
    const value = path.split('.').reduce(
        (acc, key) => (acc == null ? undefined : acc[key]),
        window.__APP_CONFIG__
    )

    // Leerstring gilt als "nicht gesetzt" — eine nicht befuellte .env-Variable
    // wuerde sonst den Fallback aushebeln.
    return value === undefined || value === null || value === '' ? fallback : value
}
